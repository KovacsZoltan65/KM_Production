<?php

namespace App\Services\Admin;

use App\Enums\PurchaseRequisitionExecutionReadinessReason as ReasonCode;
use App\Enums\PurchaseRequisitionStatus;
use App\Models\Item;
use App\Models\ItemSupplier;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use App\Repositories\Contracts\ItemSupplierRepositoryInterface;
use App\Repositories\Contracts\PurchaseRequisitionRepositoryInterface;
use App\Support\Procurement\ExecutionReadinessReason;
use App\Support\Procurement\PurchaseRequisitionExecutionReadinessResult;
use App\Support\Procurement\PurchaseRequisitionItemExecutionReadinessResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/** Pure, side-effect-free current-state evaluation before procurement execution. */
final class PurchaseRequisitionExecutionReadinessService
{
    public function __construct(
        private readonly PurchaseRequisitionRepositoryInterface $requisitions,
        private readonly ItemSupplierRepositoryInterface $itemSuppliers,
    ) {}

    public function evaluate(
        PurchaseRequisition $requisition,
        ?Carbon $businessDate = null,
        ?Carbon $checkedAt = null,
    ): PurchaseRequisitionExecutionReadinessResult {
        $checkedAt ??= now();
        $businessDate = ($businessDate ?? $checkedAt)->copy()->startOfDay();
        $requisition = $this->requisitions->findForExecutionReadiness($requisition);

        /** @var array<string, ExecutionReadinessReason> $blockingReasons */
        $blockingReasons = [];
        /** @var array<string, ExecutionReadinessReason> $warnings */
        $warnings = [];

        if ($requisition->status !== PurchaseRequisitionStatus::Approved) {
            $this->addReason($blockingReasons, new ExecutionReadinessReason(
                ReasonCode::PrNotApproved,
                parameters: ['status' => $requisition->status->value],
            ));
        }

        $supplierUsable = true;
        if ($requisition->supplier_id === null) {
            $supplierUsable = false;
            $this->addReason($blockingReasons, new ExecutionReadinessReason(ReasonCode::SupplierMissing));
        } elseif ($requisition->supplier === null || ! $requisition->supplier->is_active) {
            $supplierUsable = false;
            $this->addReason($blockingReasons, new ExecutionReadinessReason(
                ReasonCode::SupplierInactive,
                parameters: ['supplier_id' => $requisition->supplier_id],
            ));
        }

        if ($requisition->items->isEmpty()) {
            $this->addReason($blockingReasons, new ExecutionReadinessReason(ReasonCode::ItemsMissing));
        }

        $this->evaluateHeaderDates($requisition, $businessDate, $warnings);

        /** @var list<int> $itemIds */
        $itemIds = $requisition->items
            ->pluck('item_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $sourcesByItem = $supplierUsable
            ? $this->itemSuppliers
                ->eligibleForSupplierAndItemsAt((int) $requisition->supplier_id, $itemIds, $businessDate)
                ->groupBy('item_id')
            : collect();

        /** @var list<PurchaseRequisitionItemExecutionReadinessResult> $itemResults */
        $itemResults = [];
        foreach ($requisition->items->sortBy('id') as $item) {
            /** @var Collection<int, ItemSupplier> $eligibleSources */
            $eligibleSources = $sourcesByItem->get($item->item_id, collect());
            $itemResult = $this->evaluateItem(
                $item,
                $eligibleSources,
                $supplierUsable,
                $requisition->required_at,
                $requisition->proposed_supply_at,
                $businessDate,
            );
            $itemResults[] = $itemResult;

            foreach ($itemResult->blockingReasons as $reason) {
                $this->addReason($blockingReasons, $reason);
            }
            foreach ($itemResult->warnings as $warning) {
                $this->addReason($warnings, $warning);
            }
        }

        return new PurchaseRequisitionExecutionReadinessResult(
            purchaseRequisitionId: $requisition->id,
            blockingReasons: $this->sortReasons($blockingReasons),
            warnings: $this->sortReasons($warnings),
            itemResults: $itemResults,
            checkedAt: $checkedAt,
        );
    }

    /**
     * @param  Collection<int, ItemSupplier>  $eligibleSources
     */
    private function evaluateItem(
        PurchaseRequisitionItem $item,
        Collection $eligibleSources,
        bool $supplierUsable,
        ?Carbon $requiredAt,
        ?Carbon $proposedSupplyAt,
        Carbon $businessDate,
    ): PurchaseRequisitionItemExecutionReadinessResult {
        /** @var array<string, ExecutionReadinessReason> $blockers */
        $blockers = [];
        /** @var array<string, ExecutionReadinessReason> $warnings */
        $warnings = [];
        $relatedItem = $item->getRelation('item');
        $itemNumber = $relatedItem instanceof Item ? $relatedItem->item_number : '#'.$item->item_id;

        $reason = fn (ReasonCode $code, array $parameters = []): ExecutionReadinessReason => new ExecutionReadinessReason(
            code: $code,
            purchaseRequisitionItemId: $item->id,
            itemId: $item->item_id,
            itemNumber: $itemNumber,
            parameters: ['item' => $itemNumber, ...$parameters],
        );

        $itemUsable = $relatedItem instanceof Item && $relatedItem->is_active;
        if (! $itemUsable) {
            $this->addReason($blockers, $reason(ReasonCode::ItemInactive));
        } elseif ($item->unit !== $relatedItem->unit) {
            $this->addReason($blockers, $reason(ReasonCode::ItemUnitInvalid));
        }

        $currentSource = null;
        if ($supplierUsable && $itemUsable) {
            if ($eligibleSources->isEmpty()) {
                $this->addReason($blockers, $reason(ReasonCode::ItemSupplierInvalid));
            } elseif ($eligibleSources->count() > 1) {
                $this->addReason($blockers, $reason(ReasonCode::ItemSupplierAmbiguous));
            } else {
                $currentSource = $eligibleSources->sole();
                if (trim($currentSource->purchase_unit) === ''
                    || ($this->scaledQuantity((string) $currentSource->conversion_factor, 6) ?? 0) <= 0) {
                    $this->addReason($blockers, $reason(ReasonCode::ItemSupplierInvalid));
                    $currentSource = null;
                }
            }
        }

        $planned = $this->scaledQuantity((string) $item->planned_quantity, 3);
        $requested = $this->scaledQuantity((string) $item->quantity, 3);
        $excess = $this->scaledQuantity((string) $item->replenishment_excess_quantity, 3);

        if ($planned === null || $requested === null || $excess === null
            || $planned < 0 || $requested <= 0 || $excess < 0
            || $requested < $planned || $planned + $excess !== $requested) {
            $this->addReason($blockers, $reason(ReasonCode::QuantityInvariantFailed));
        }

        if ($item->proposalSources->isNotEmpty()) {
            $sourceTotal = 0;
            $sourceTotalValid = true;
            foreach ($item->proposalSources as $proposalSource) {
                $sourceQuantity = $this->scaledQuantity((string) $proposalSource->quantity, 3);
                if ($sourceQuantity === null) {
                    $sourceTotalValid = false;
                    break;
                }
                $sourceTotal += $sourceQuantity;
            }

            if (! $sourceTotalValid || $planned === null || $sourceTotal !== $planned) {
                $this->addReason($blockers, $reason(ReasonCode::ProposalLineageMismatch));
            }
        }

        $calculated = $item->replenishment_calculated_at !== null
            && $item->replenishment_item_supplier_id !== null
            && $item->replenishment_strategy !== null;

        if (! $calculated) {
            $this->addReason($blockers, $reason(ReasonCode::ReplenishmentNotCalculated));
        } elseif ($currentSource !== null && $this->replenishmentIsStale($item, $currentSource)) {
            $this->addReason($blockers, $reason(ReasonCode::ReplenishmentStale));
        }

        if ($currentSource !== null) {
            if ($currentSource->unit_price === null || $currentSource->currency === null) {
                $this->addReason($warnings, $reason(ReasonCode::PriceMissing));
            }

            $leadTimeLate = $requiredAt !== null
                && $currentSource->lead_time_days !== null
                && $businessDate->copy()->addDays($currentSource->lead_time_days)->gt($requiredAt);
            $proposedSupplyLate = $requiredAt !== null
                && $proposedSupplyAt !== null
                && $proposedSupplyAt->gt($requiredAt);

            if ($leadTimeLate || $proposedSupplyLate) {
                $this->addReason($warnings, $reason(ReasonCode::ExpectedLateSupply));
            }
        }

        return new PurchaseRequisitionItemExecutionReadinessResult(
            purchaseRequisitionItemId: $item->id,
            itemId: $item->item_id,
            itemNumber: $itemNumber,
            supplierId: $currentSource?->supplier_id,
            itemSupplierId: $currentSource?->id,
            blockingReasons: $this->sortReasons($blockers),
            warnings: $this->sortReasons($warnings),
        );
    }

    private function replenishmentIsStale(PurchaseRequisitionItem $item, ItemSupplier $source): bool
    {
        $expectedStrategy = match (true) {
            $this->positiveQuantity($source->minimum_order_quantity)
                && $source->order_multiple !== null => 'moq_and_order_multiple',
            $this->positiveQuantity($source->minimum_order_quantity) => 'moq',
            $source->order_multiple !== null => 'order_multiple',
            default => 'exact',
        };

        return $item->replenishment_item_supplier_id !== $source->id
            || ! $this->sameNullableQuantity(
                $item->replenishment_minimum_order_quantity,
                $source->minimum_order_quantity,
            )
            || ! $this->sameNullableQuantity(
                $item->replenishment_order_multiple,
                $source->order_multiple,
            )
            || $item->replenishment_strategy !== $expectedStrategy;
    }

    private function positiveQuantity(mixed $value): bool
    {
        return $value !== null && ($this->scaledQuantity((string) $value, 3) ?? 0) > 0;
    }

    private function sameNullableQuantity(mixed $left, mixed $right): bool
    {
        if ($left === null || $right === null) {
            return $left === null && $right === null;
        }

        return $this->scaledQuantity((string) $left, 3) === $this->scaledQuantity((string) $right, 3);
    }

    private function scaledQuantity(string $value, int $scale): ?int
    {
        $value = trim($value);
        if (! preg_match('/^(-?)(\d+)(?:\.(\d+))?$/', $value, $matches)) {
            return null;
        }

        $fraction = $matches[3] ?? '';
        if (strlen(ltrim($matches[2], '0')) > 18 - $scale
            || (strlen($fraction) > $scale && trim(substr($fraction, $scale), '0') !== '')) {
            return null;
        }

        $factor = 10 ** $scale;
        $scaled = ((int) $matches[2] * $factor)
            + (int) str_pad(substr($fraction, 0, $scale), $scale, '0');

        return $matches[1] === '-' ? -$scaled : $scaled;
    }

    /** @param array<string, ExecutionReadinessReason> $reasons */
    private function addReason(array &$reasons, ExecutionReadinessReason $reason): void
    {
        $reasons[$reason->uniqueKey()] = $reason;
    }

    /**
     * @param  array<string, ExecutionReadinessReason>  $reasons
     * @return list<ExecutionReadinessReason>
     */
    private function sortReasons(array $reasons): array
    {
        $reasons = array_values($reasons);
        usort($reasons, fn (ExecutionReadinessReason $left, ExecutionReadinessReason $right): int => [$left->code->order(), $left->purchaseRequisitionItemId ?? 0]
            <=> [$right->code->order(), $right->purchaseRequisitionItemId ?? 0]);

        return $reasons;
    }

    /** @param array<string, ExecutionReadinessReason> $warnings */
    private function evaluateHeaderDates(
        PurchaseRequisition $requisition,
        Carbon $businessDate,
        array &$warnings,
    ): void {
        if ($requisition->required_at === null) {
            $this->addReason($warnings, new ExecutionReadinessReason(ReasonCode::RequiredDateMissing));

            return;
        }

        if ($requisition->required_at->lt($businessDate)) {
            $this->addReason($warnings, new ExecutionReadinessReason(
                ReasonCode::RequiredDatePassed,
                parameters: ['required_at' => $requisition->required_at->toDateString()],
            ));
        }
    }
}
