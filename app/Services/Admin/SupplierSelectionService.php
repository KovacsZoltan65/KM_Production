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
use App\Support\Procurement\SupplierSelectionCandidate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Resolves the common eligible Supplier set for a PR and persists an explicit manual choice. */
final class SupplierSelectionService
{
    public function __construct(
        private readonly ItemSupplierRepositoryInterface $itemSuppliers,
        private readonly PurchaseRequisitionRepositoryInterface $requisitions,
        private readonly AuditLogService $audit,
        private readonly BusinessCacheInvalidator $cacheInvalidator,
    ) {}

    /** @return Collection<int, SupplierSelectionCandidate> */
    public function candidatesForPurchaseRequisition(
        PurchaseRequisition $requisition,
        ?Carbon $selectionDate = null,
    ): Collection {
        $requisition = $this->requisitions->findForSupplierSelection($requisition);

        return $this->commonCandidates($requisition, $selectionDate ?? today());
    }

    public function selectSupplier(
        PurchaseRequisition $requisition,
        int $supplierId,
        ?User $causer = null,
        ?Carbon $selectionDate = null,
    ): PurchaseRequisition {
        $changed = false;

        $requisition = DB::transaction(function () use (
            $requisition,
            $supplierId,
            $causer,
            $selectionDate,
            &$changed,
        ): PurchaseRequisition {
            $locked = $this->requisitions->lockForSupplierSelection($requisition->id);

            if ($locked->status !== PurchaseRequisitionStatus::Draft) {
                throw ValidationException::withMessages([
                    'status' => __('procurement.supplier_selection.validation.only_draft'),
                ]);
            }

            $candidates = $this->commonCandidates($locked, $selectionDate ?? today(), true);
            $candidate = $candidates->first(
                fn (SupplierSelectionCandidate $candidate): bool => $candidate->supplierId === $supplierId,
            );

            if ($candidate === null) {
                throw ValidationException::withMessages([
                    'supplier_id' => __('procurement.supplier_selection.validation.not_eligible'),
                ]);
            }

            if ($locked->supplier_id !== null) {
                if ($locked->supplier_id !== $supplierId) {
                    throw ValidationException::withMessages([
                        'supplier_id' => __('procurement.supplier_selection.validation.already_selected'),
                    ]);
                }

                return $locked;
            }

            $locked = $this->requisitions->assignSupplier($locked, $supplierId);
            $this->audit->log('supplier_selected', $locked, [
                'purchase_requisition_id' => $locked->id,
                'supplier_id' => $supplierId,
                'selection_mode' => 'manual',
                'candidate_count' => $candidates->count(),
            ], $causer);
            $changed = true;

            return $locked;
        });

        if ($changed) {
            $this->cacheInvalidator->procurementChanged();
        }

        return $requisition;
    }

    /** @return Collection<int, SupplierSelectionCandidate> */
    private function commonCandidates(
        PurchaseRequisition $requisition,
        Carbon $selectionDate,
        bool $failOnInactiveItem = false,
    ): Collection {
        $items = $requisition->items;

        if ($items->isEmpty()) {
            return collect();
        }

        $hasInactiveItem = $items->contains(
            fn (PurchaseRequisitionItem $item): bool => ! $item->item->is_active,
        );

        if ($hasInactiveItem) {
            if ($failOnInactiveItem) {
                throw ValidationException::withMessages([
                    'supplier_id' => __('procurement.supplier_selection.validation.inactive_item'),
                ]);
            }

            return collect();
        }

        /** @var list<int> $itemIds */
        $itemIds = $items->pluck('item_id')->map(fn (mixed $id): int => (int) $id)->unique()->sort()->values()->all();
        $sources = $this->itemSuppliers->eligibleForItemsAt($itemIds, $selectionDate);
        $requiredItemCount = count($itemIds);

        return $sources
            ->groupBy('supplier_id')
            ->filter(fn (Collection $supplierSources): bool => $supplierSources
                ->pluck('item_id')->unique()->count() === $requiredItemCount)
            ->map(function (Collection $supplierSources): SupplierSelectionCandidate {
                /** @var ItemSupplier $first */
                $first = $supplierSources->first();

                return new SupplierSelectionCandidate(
                    supplierId: $first->supplier_id,
                    supplierCode: $first->supplier->code,
                    supplierName: $first->supplier->name,
                    sources: $supplierSources
                        ->sortBy('item_id')
                        ->map(fn (ItemSupplier $source): array => [
                            'item_supplier_id' => $source->id,
                            'item_id' => $source->item_id,
                            'item_number' => $source->item->item_number,
                            'item_name' => $source->item->name,
                            'preferred' => $source->is_preferred,
                            'priority' => $source->priority,
                            'lead_time_days' => $source->lead_time_days,
                            'unit_price' => $source->unit_price === null ? null : (string) $source->unit_price,
                            'currency' => $source->currency,
                            'purchase_unit' => $source->purchase_unit,
                            'conversion_factor' => (string) $source->conversion_factor,
                            'minimum_order_quantity' => $source->minimum_order_quantity === null
                                ? null
                                : (string) $source->minimum_order_quantity,
                            'order_multiple' => $source->order_multiple === null
                                ? null
                                : (string) $source->order_multiple,
                            'valid_from' => $source->valid_from?->toDateString(),
                            'valid_until' => $source->valid_until?->toDateString(),
                        ])
                        ->values()
                        ->all(),
                );
            })
            ->values()
            ->sort(function (SupplierSelectionCandidate $left, SupplierSelectionCandidate $right): int {
                $nameOrder = strcasecmp($left->supplierName, $right->supplierName);

                return $nameOrder !== 0 ? $nameOrder : $left->supplierId <=> $right->supplierId;
            })
            ->values();
    }
}
