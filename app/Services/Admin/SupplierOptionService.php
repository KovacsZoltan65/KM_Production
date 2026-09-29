<?php

namespace App\Services\Admin;

use App\Enums\ItemType;
use App\Models\Item;
use App\Models\ItemSupplier;
use App\Models\Supplier;
use App\Repositories\Contracts\ItemRepositoryInterface;
use App\Repositories\Contracts\ItemSupplierRepositoryInterface;
use App\Support\Procurement\ProcurementDecimal;
use App\Support\Procurement\ProcurementInputValidation;
use App\Support\Procurement\ProcurementRequirementInput;
use App\Support\Procurement\ProcurementTiming;
use App\Support\Procurement\ReplenishmentQuantityResult;
use App\Support\Procurement\SupplierOption;
use App\Support\Procurement\SupplierOptionEvaluationException;
use App\Support\Procurement\SupplierOptionQuery;
use App\Support\Procurement\SupplierOptionReadSnapshot;
use App\Support\Procurement\SupplierOptionResult;
use App\Support\Procurement\SupplierOptionValue as Value;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Read-only capability for trusted application callers. Before disclosing the result,
 * the caller must authorize item-suppliers.view and validate its source lifecycle.
 * Provenance is an assertion, not authorization. No endpoint or lifecycle adapter here.
 */
final class SupplierOptionService
{
    public function __construct(
        private readonly ItemRepositoryInterface $items,
        private readonly ItemSupplierRepositoryInterface $sources,
        private readonly PurchaseRequisitionReplenishmentService $replenishment,
        private readonly SupplierOptionReadSnapshot $snapshot,
    ) {}

    public function evaluate(SupplierOptionQuery $query): SupplierOptionResult
    {
        return $this->snapshot->evaluate(function () use ($query): SupplierOptionResult {
            $input = $query->requirement;
            $item = $this->items->findForSupplierOptions($input->itemId);
            if ($item === null || $item->trashed() || $item->id !== $input->itemId
                || $item->item_type !== ItemType::PurchasedMaterial) {
                ProcurementInputValidation::fail('item_id');
            }
            if ($item->unit !== $input->unit) {
                ProcurementInputValidation::fail('unit');
            }

            $known = $this->sources->knownSourcesForItem($item->id);
            $eligible = $this->sources->eligibleForItemsAt([$item->id], Carbon::parse($input->evaluationDate));
            $membership = array_fill_keys($eligible->pluck('id')->all(), true);
            $knownIds = [];
            $options = [];
            foreach ($known as $source) {
                if (isset($knownIds[$source->id])) {
                    throw new SupplierOptionEvaluationException('SUPPLIER_OPTION_DUPLICATE_SOURCE');
                }
                $knownIds[$source->id] = true;
                $this->assertSourceIntegrity($source, $item);
                $options[] = $this->evaluateSource($input, $item, $source, isset($membership[$source->id]));
            }
            if (array_diff_key($membership, $knownIds) !== []) {
                throw new SupplierOptionEvaluationException('SUPPLIER_OPTION_MEMBERSHIP_MISMATCH');
            }

            return new SupplierOptionResult(
                item: [
                    'id' => $item->id,
                    'item_number' => $item->item_number,
                    'name' => $item->name,
                    'item_type' => $item->item_type->value,
                    'unit' => $item->unit,
                    'is_active' => $item->is_active,
                ],
                requirement: $input,
                evaluatedAt: now()->toIso8601String(),
                options: $options,
            );
        });
    }

    private function assertSourceIntegrity(ItemSupplier $source, Item $item): void
    {
        if (! $source->relationLoaded('item') || ! $source->relationLoaded('supplier')) {
            throw new SupplierOptionEvaluationException('SUPPLIER_OPTION_RELATIONS_NOT_LOADED');
        }
        $parentItem = $source->getRelation('item');
        $supplier = $source->getRelation('supplier');
        if (! $parentItem instanceof Item || ! $supplier instanceof Supplier
            || $source->item_id !== $item->id || $parentItem->id !== $item->id
            || $source->supplier_id !== $supplier->id || $parentItem->trashed()
            || $parentItem->unit !== $item->unit || $parentItem->is_active !== $item->is_active
            || $parentItem->item_type !== $item->item_type) {
            throw new SupplierOptionEvaluationException('SUPPLIER_OPTION_SOURCE_INTEGRITY');
        }
    }

    private function evaluateSource(ProcurementRequirementInput $input, Item $item, ItemSupplier $source, bool $isEligible): SupplierOption
    {
        $reasons = $this->eligibilityReasons($item, $source, $input->evaluationDate);
        if ($isEligible !== ($reasons === [])) {
            throw new SupplierOptionEvaluationException('SUPPLIER_OPTION_DIAGNOSTICS_MISMATCH');
        }

        $missing = [];
        $inconsistent = [];
        $quantity = null;
        $fitReasons = $reasons;
        if ($isEligible) {
            if (trim((string) $source->purchase_unit) === '' || ($source->lead_time_days !== null && $source->lead_time_days < 0)) {
                $fitReasons[] = 'INVALID_POLICY';
                $inconsistent[] = ['field' => trim((string) $source->purchase_unit) === '' ? 'ordering.purchase_unit' : 'delivery.lead_time_days', 'reason' => 'INVALID_POLICY'];
            } else {
                try {
                    // The authoritative input reaches the existing calculator unchanged.
                    $quantity = $this->replenishment->calculateQuantity($input->requiredQuantity, $input->unit, $source);
                } catch (ValidationException $exception) {
                    $fitReasons[] = 'INVALID_POLICY';
                    foreach (array_keys($exception->errors()) as $field) {
                        $inconsistent[] = ['field' => $field === 'items' ? 'ordering' : 'ordering.'.$field, 'reason' => 'INVALID_POLICY'];
                    }
                }
            }
        }

        $usable = $isEligible && $quantity !== null;
        [$feasible, $expected, $dateFit, $timingReasons] = $this->requirementFit($input, $source, $usable);
        $fitReasons = [...$fitReasons, ...$timingReasons];
        foreach ([
            ['requirement.required_date', $input->requiredDate, 'MISSING_REQUIRED_DATE'],
            ['delivery.lead_time_days', $source->lead_time_days, 'MISSING_LEAD_TIME'],
            ['commercial.reference_unit_price', $source->unit_price, 'MISSING_REFERENCE_PRICE'],
            ['commercial.currency', $source->currency, 'MISSING_CURRENCY'],
        ] as [$field, $value, $reason]) {
            if ($value === null) {
                $missing[] = ['field' => $field, 'reason' => $reason];
            }
        }
        if (($source->unit_price === null) !== ($source->currency === null)) {
            $inconsistent[] = ['field' => 'commercial', 'reason' => 'PRICE_CURRENCY_INCONSISTENT'];
        }

        return new SupplierOption(
            supplier: [
                'id' => $source->supplier->id,
                'code' => $source->supplier->code,
                'name' => $source->supplier->name,
                'is_active' => $source->supplier->is_active,
                'deleted_at' => $source->supplier->deleted_at === null ? Value::notApplicable('NOT_DELETED') : Value::known($source->supplier->deleted_at->toIso8601String()),
            ],
            relationship: [
                'id' => $source->id,
                'supplier_item_code' => $source->supplier_item_code === null ? Value::unknown('NOT_RECORDED') : Value::known($source->supplier_item_code),
                'is_active' => $source->is_active,
                'is_approved' => $source->is_approved,
                'is_preferred' => $source->is_preferred,
                'priority' => $source->priority,
                'valid_from' => $source->valid_from === null ? Value::notApplicable('UNBOUNDED_START') : Value::known($source->valid_from->toDateString()),
                'valid_until' => $source->valid_until === null ? Value::notApplicable('UNBOUNDED_END') : Value::known($source->valid_until->toDateString()),
                'updated_at' => $source->updated_at === null ? Value::unknown('NOT_RECORDED') : Value::known($source->updated_at->toIso8601String()),
            ],
            eligibility: ['is_eligible' => $isEligible, 'reasons' => $reasons],
            requirementFit: ['is_usable' => $usable, 'objectively_feasible' => $feasible, 'scope' => 'quantity_and_date', 'reasons' => $fitReasons],
            ordering: $this->ordering($source, $quantity),
            delivery: [
                'lead_time_days' => $source->lead_time_days === null ? Value::unknown('MISSING_LEAD_TIME') : Value::known($source->lead_time_days),
                'expected_date' => $expected,
                'date_fit' => $dateFit,
            ],
            commercial: [
                'reference_unit_price' => $source->unit_price === null ? Value::unknown('MISSING_REFERENCE_PRICE') : Value::known((string) $source->unit_price),
                'currency' => $source->currency === null ? Value::unknown('MISSING_CURRENCY') : Value::known($source->currency),
                'price_basis' => Value::unknown('PRICE_BASIS_UNDEFINED'),
                'estimated_reference_value' => $input->requiredQuantity === '0.000' ? Value::notApplicable('NO_PROCUREMENT_REQUIRED') : Value::unknown('PRICE_BASIS_UNDEFINED'),
            ],
            dataQuality: ['missing' => $missing, 'inconsistent' => $inconsistent, 'restricted' => []],
        );
    }

    /** @return list<string> */
    private function eligibilityReasons(Item $item, ItemSupplier $source, string $date): array
    {
        $conditions = [
            'ITEM_INACTIVE' => ! $item->is_active,
            'SUPPLIER_INACTIVE' => ! $source->supplier->is_active,
            'SUPPLIER_DELETED' => $source->supplier->trashed(),
            'SOURCE_INACTIVE' => ! $source->is_active,
            'SOURCE_NOT_APPROVED' => ! $source->is_approved,
            'BEFORE_VALID_FROM' => $source->valid_from !== null && $source->valid_from->toDateString() > $date,
            'AFTER_VALID_UNTIL' => $source->valid_until !== null && $source->valid_until->toDateString() < $date,
        ];

        return array_keys(array_filter($conditions));
    }

    /** @return array{Value, Value, Value, list<string>} */
    private function requirementFit(ProcurementRequirementInput $input, ItemSupplier $source, bool $usable): array
    {
        if (! $usable) {
            return [Value::known(false), Value::notApplicable('UNUSABLE_SOURCE'), Value::notApplicable('UNUSABLE_SOURCE'), []];
        }
        if ($input->requiredQuantity === '0.000') {
            $notApplicable = Value::notApplicable('NO_PROCUREMENT_REQUIRED');

            return [$notApplicable, $notApplicable, $notApplicable, ['NO_PROCUREMENT_REQUIRED']];
        }

        $expected = ProcurementTiming::expectedDate(Carbon::parse($input->evaluationDate), $source->lead_time_days);
        $required = $input->requiredDate === null ? null : Carbon::parse($input->requiredDate);
        $late = ProcurementTiming::isLate($expected, $required);
        $expectedValue = $expected === null ? Value::unknown('MISSING_LEAD_TIME') : Value::known($expected->toDateString());
        if ($late === null) {
            $reasons = [];
            if ($required === null) {
                $reasons[] = 'MISSING_REQUIRED_DATE';
            }
            if ($expected === null) {
                $reasons[] = 'MISSING_LEAD_TIME';
            }
            $unknown = Value::unknown($reasons[0]);

            return [$unknown, $expectedValue, $unknown, $reasons];
        }

        return [Value::known(! $late), $expectedValue, Value::known($late ? 'late' : 'on_time'), $late ? ['EXPECTED_LATE_SUPPLY'] : []];
    }

    /** @return array{minimum_order_quantity: Value, order_multiple: Value, strategy: Value, effective_order_quantity: Value, excess_quantity: Value, purchase_unit: Value, conversion_factor: Value} */
    private function ordering(ItemSupplier $source, ?ReplenishmentQuantityResult $quantity): array
    {
        return [
            'minimum_order_quantity' => $source->minimum_order_quantity === null ? Value::notApplicable('NO_MOQ') : Value::known((string) $source->minimum_order_quantity),
            'order_multiple' => $source->order_multiple === null ? Value::notApplicable('NO_ORDER_MULTIPLE') : Value::known((string) $source->order_multiple),
            'strategy' => $quantity === null ? Value::notApplicable('UNUSABLE_SOURCE') : Value::known($quantity->strategy),
            'effective_order_quantity' => $quantity === null ? Value::notApplicable('UNUSABLE_SOURCE') : Value::known($quantity->adjustedQuantity),
            'excess_quantity' => $quantity === null ? Value::notApplicable('UNUSABLE_SOURCE') : Value::known($quantity->excessQuantity),
            'purchase_unit' => trim((string) $source->purchase_unit) === '' ? Value::unknown('INVALID_POLICY') : Value::known($source->purchase_unit),
            'conversion_factor' => $this->conversionFactor($source),
        ];
    }

    private function conversionFactor(ItemSupplier $source): Value
    {
        try {
            $factor = ProcurementDecimal::toScaledInteger((string) $source->conversion_factor, 6, 'conversion_factor');
        } catch (ValidationException) {
            return Value::unknown('INVALID_POLICY');
        }

        return $factor <= 0 ? Value::unknown('INVALID_POLICY') : Value::known((string) $source->conversion_factor);
    }
}
