<?php

namespace App\Services\Admin;

use App\Enums\PurchaseRequisitionStatus;
use App\Models\ItemSupplier;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use App\Models\User;
use App\Repositories\Contracts\ItemSupplierRepositoryInterface;
use App\Repositories\Contracts\PurchaseRequisitionRepositoryInterface;
use App\Services\AuditLogService;
use App\Services\BusinessCacheInvalidator;
use App\Support\Procurement\ReplenishmentQuantityResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Applies the selected procurement source's base-unit quantity policy to a Draft PR. */
final class PurchaseRequisitionReplenishmentService
{
    private const MAX_THOUSANDTHS = 999_999_999_999_999_999;

    public function __construct(
        private readonly PurchaseRequisitionRepositoryInterface $requisitions,
        private readonly ItemSupplierRepositoryInterface $itemSuppliers,
        private readonly AuditLogService $audit,
        private readonly BusinessCacheInvalidator $cacheInvalidator,
    ) {}

    public function calculateForPurchaseRequisition(
        PurchaseRequisition $requisition,
        ?User $causer = null,
        ?Carbon $calculationDate = null,
    ): PurchaseRequisition {
        $requisition = DB::transaction(function () use ($requisition, $causer, $calculationDate): PurchaseRequisition {
            $locked = $this->requisitions->lockForReplenishment($requisition->id);

            if ($locked->status !== PurchaseRequisitionStatus::Draft) {
                $this->fail('status', 'procurement.replenishment.validation.only_draft');
            }

            if ($locked->supplier_id === null) {
                $this->fail('supplier_id', 'procurement.replenishment.validation.supplier_required');
            }

            if ($locked->items->isEmpty()) {
                $this->fail('items', 'procurement.replenishment.validation.items_required');
            }

            /** @var list<int> $itemIds */
            $itemIds = $locked->items->pluck('item_id')->map(fn (mixed $id): int => (int) $id)
                ->unique()->sort()->values()->all();
            $sources = $this->itemSuppliers->eligibleForSupplierAndItemsAt(
                $locked->supplier_id,
                $itemIds,
                $calculationDate ?? today(),
            );
            $sourcesByItem = $sources->groupBy('item_id');

            /** @var Collection<int, array{item: PurchaseRequisitionItem, result: ReplenishmentQuantityResult}> $calculations */
            $calculations = collect();

            foreach ($locked->items->sortBy('id') as $item) {
                /** @var Collection<int, ItemSupplier> $itemSources */
                $itemSources = $sourcesByItem->get($item->item_id, collect());

                if ($itemSources->count() !== 1) {
                    $this->fail('items', 'procurement.replenishment.validation.source_required', ['item' => $item->item->item_number]);
                }

                /** @var ItemSupplier $source */
                $source = $itemSources->first();
                if ($item->unit !== $item->item->unit) {
                    $this->fail('items', 'procurement.replenishment.validation.invalid_item_unit', ['item' => $item->item->item_number]);
                }
                $this->assertPlannedLineage($item);
                $calculations->push([
                    'item' => $item,
                    'result' => $this->calculateQuantity((string) $item->planned_quantity, $item->unit, $source),
                ]);
            }

            $changedItems = 0;
            $auditItems = [];
            $calculatedAt = now();

            foreach ($calculations as $calculation) {
                $item = $calculation['item'];
                $result = $calculation['result'];
                $changedItems += (string) $item->quantity !== $result->adjustedQuantity ? 1 : 0;

                $this->requisitions->updateItemReplenishment($item, [
                    'quantity' => $result->adjustedQuantity,
                    'replenishment_excess_quantity' => $result->excessQuantity,
                    'replenishment_item_supplier_id' => $result->itemSupplierId,
                    'replenishment_minimum_order_quantity' => $result->minimumOrderQuantity,
                    'replenishment_order_multiple' => $result->orderMultiple,
                    'replenishment_strategy' => $result->strategy,
                    'replenishment_calculated_at' => $calculatedAt,
                ]);

                $auditItems[] = [
                    'purchase_requisition_item_id' => $item->id,
                    'item_id' => $result->itemId,
                    'item_supplier_id' => $result->itemSupplierId,
                    'planned_quantity' => $result->baseRequiredQuantity,
                    'requested_quantity' => $result->adjustedQuantity,
                    'excess_quantity' => $result->excessQuantity,
                    'unit' => $result->baseUnit,
                    'minimum_order_quantity' => $result->minimumOrderQuantity,
                    'order_multiple' => $result->orderMultiple,
                    'strategy' => $result->strategy,
                ];
            }

            $this->audit->log('purchase_requisition_replenishment_calculated', $locked, [
                'purchase_requisition_id' => $locked->id,
                'items_count' => $calculations->count(),
                'changed_items_count' => $changedItems,
                'items' => $auditItems,
            ], $causer);

            return $locked->refresh();
        });

        $this->cacheInvalidator->procurementChanged();

        return $requisition;
    }

    public function calculateQuantity(
        string $baseRequiredQuantity,
        string $baseUnit,
        ItemSupplier $source,
    ): ReplenishmentQuantityResult {
        $base = $this->toScaledInteger($baseRequiredQuantity, 3, 'base_required_quantity');

        if ($base < 0) {
            $this->fail('base_required_quantity', 'procurement.replenishment.validation.negative_base');
        }

        if ($source->purchase_unit === '' || $this->toScaledInteger((string) $source->conversion_factor, 6, 'conversion_factor') <= 0) {
            $this->fail('items', 'procurement.replenishment.validation.invalid_policy');
        }

        $moq = $source->minimum_order_quantity === null
            ? null
            : $this->toScaledInteger((string) $source->minimum_order_quantity, 3, 'minimum_order_quantity');
        $multiple = $source->order_multiple === null
            ? null
            : $this->toScaledInteger((string) $source->order_multiple, 3, 'order_multiple');

        if (($moq !== null && $moq < 0) || ($multiple !== null && $multiple <= 0)) {
            $this->fail('items', 'procurement.replenishment.validation.invalid_policy');
        }

        $hasMoq = $moq !== null && $moq > 0;
        $hasMultiple = $multiple !== null;
        $strategy = match (true) {
            $hasMoq && $hasMultiple => 'moq_and_order_multiple',
            $hasMoq => 'moq',
            $hasMultiple => 'order_multiple',
            default => 'exact',
        };

        $adjusted = $base;
        if ($base > 0) {
            $adjusted = $hasMoq ? max($base, $moq) : $base;
            if ($hasMultiple) {
                $adjusted = intdiv($adjusted + $multiple - 1, $multiple) * $multiple;
            }
        }

        if ($adjusted < $base || $adjusted > self::MAX_THOUSANDTHS) {
            $this->fail('items', 'procurement.replenishment.validation.invalid_result');
        }

        return new ReplenishmentQuantityResult(
            itemId: $source->item_id,
            itemSupplierId: $source->id,
            baseRequiredQuantity: $this->fromThousandths($base),
            adjustedQuantity: $this->fromThousandths($adjusted),
            excessQuantity: $this->fromThousandths($adjusted - $base),
            baseUnit: $baseUnit,
            minimumOrderQuantity: $moq === null ? null : $this->fromThousandths($moq),
            orderMultiple: $multiple === null ? null : $this->fromThousandths($multiple),
            strategy: $strategy,
            purchaseUnit: $source->purchase_unit,
            conversionFactor: (string) $source->conversion_factor,
        );
    }

    private function assertPlannedLineage(PurchaseRequisitionItem $item): void
    {
        $planned = $this->toScaledInteger((string) $item->planned_quantity, 3, 'planned_quantity');

        if ($planned < 0) {
            $this->fail('items', 'procurement.replenishment.validation.negative_base');
        }

        if ($item->proposalSources->isEmpty()) {
            return;
        }

        $sourceTotal = $item->proposalSources->sum(
            fn ($source): int => $this->toScaledInteger((string) $source->quantity, 3, 'source_quantity'),
        );

        if ($sourceTotal !== $planned) {
            $this->fail('items', 'procurement.replenishment.validation.source_mismatch');
        }
    }

    private function toScaledInteger(string $value, int $scale, string $field): int
    {
        $value = trim($value);
        if (! preg_match('/^(-?)(\d+)(?:\.(\d+))?$/', $value, $matches)) {
            $this->fail($field, 'procurement.replenishment.validation.invalid_quantity');
        }

        $fraction = $matches[3] ?? '';
        if (strlen(ltrim($matches[2], '0')) > 18 - $scale) {
            $this->fail($field, 'procurement.replenishment.validation.invalid_result');
        }
        if (strlen($fraction) > $scale && trim(substr($fraction, $scale), '0') !== '') {
            $this->fail($field, 'procurement.replenishment.validation.invalid_precision');
        }

        $factor = 10 ** $scale;
        $whole = (int) $matches[2];
        $scaled = ($whole * $factor) + (int) str_pad(substr($fraction, 0, $scale), $scale, '0');

        return $matches[1] === '-' ? -$scaled : $scaled;
    }

    private function fromThousandths(int $quantity): string
    {
        return sprintf('%d.%03d', intdiv($quantity, 1000), $quantity % 1000);
    }

    /** @param array<string, string|int> $replace */
    private function fail(string $field, string $translationKey, array $replace = []): never
    {
        throw ValidationException::withMessages([$field => __($translationKey, $replace)]);
    }
}
