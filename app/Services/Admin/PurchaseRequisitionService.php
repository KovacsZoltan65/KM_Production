<?php

namespace App\Services\Admin;

use App\Enums\PurchaseOrderItemStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequisitionItemStatus;
use App\Enums\PurchaseRequisitionStatus;
use App\Models\MaterialRequirement;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use App\Models\User;
use App\Repositories\Contracts\PurchaseRequisitionRepositoryInterface;
use App\Services\AuditLogService;
use App\Services\BusinessCacheInvalidator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A beszerzési igények létrehozását, jóváhagyását és rendelésre váltását kezeli.
 *
 * Az összetett írásokat tranzakcióban végzi, és a fontos műveleteket auditnaplózza.
 */
class PurchaseRequisitionService
{
    public function __construct(
        private readonly PurchaseRequisitionRepositoryInterface $purchaseRequisitions,
        private readonly AuditLogService $auditLogService,
        private readonly BusinessCacheInvalidator $cacheInvalidator,
    ) {}

    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->purchaseRequisitions->paginateForAdminIndex($filters, $perPage);
    }

    public function findForShow(PurchaseRequisition $purchaseRequisition): PurchaseRequisition
    {
        return $this->purchaseRequisitions->findForShow($purchaseRequisition);
    }

    public function create(array $attributes, ?User $causer = null): PurchaseRequisition
    {
        $requisition = DB::transaction(function () use ($attributes, $causer): PurchaseRequisition {
            $items = $attributes['items'] ?? [];
            unset($attributes['items']);

            $requisition = PurchaseRequisition::query()->create([
                'requisition_number' => $attributes['requisition_number'] ?? $this->nextRequisitionNumber(),
                'status' => $attributes['status'] ?? PurchaseRequisitionStatus::Requested->value,
                'requested_by' => $causer?->id,
                'requested_at' => $attributes['requested_at'] ?? now(),
                'notes' => $attributes['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                $requisition->items()->create([
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                    'status' => PurchaseRequisitionItemStatus::Requested->value,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $this->auditLogService->log('purchase_requisition_created', $requisition, [], $causer);

            return $requisition->refresh();
        });

        $this->cacheInvalidator->procurementChanged();

        return $requisition;
    }

    public function update(PurchaseRequisition $purchaseRequisition, array $attributes, ?User $causer = null): PurchaseRequisition
    {
        $purchaseRequisition->update([
            'notes' => $attributes['notes'] ?? $purchaseRequisition->notes,
        ]);

        $this->auditLogService->log('purchase_requisition_updated', $purchaseRequisition, [], $causer);
        $this->cacheInvalidator->procurementChanged();

        return $purchaseRequisition->refresh();
    }

    public function delete(PurchaseRequisition $purchaseRequisition, ?User $causer = null): void
    {
        $this->auditLogService->log('purchase_requisition_deleted', $purchaseRequisition, [], $causer);
        $purchaseRequisition->delete();
        $this->cacheInvalidator->procurementChanged();
    }

    public function approve(PurchaseRequisition $purchaseRequisition, ?User $causer = null): PurchaseRequisition
    {
        if (! \in_array($purchaseRequisition->status, [PurchaseRequisitionStatus::Draft, PurchaseRequisitionStatus::Requested], true)) {
            throw ValidationException::withMessages(['status' => __('procurement.purchase_requisitions.validation.only_draft_requested_approve')]);
        }

        $purchaseRequisition->update(['status' => PurchaseRequisitionStatus::Approved->value]);
        $purchaseRequisition->items()->update(['status' => PurchaseRequisitionItemStatus::Requested->value]);

        $this->auditLogService->log('purchase_requisition_approved', $purchaseRequisition, [], $causer);
        $this->cacheInvalidator->procurementChanged();

        return $purchaseRequisition->refresh();
    }

    public function generateFromMaterialRequirements(?User $causer = null): PurchaseRequisition
    {
        $requisition = DB::transaction(function () use ($causer): PurchaseRequisition {
            $requirements = MaterialRequirement::query()
                ->with(['requiredItem', 'customerOrderItem.customerOrder'])
                ->where('missing_quantity', '>', 0)
                ->whereDoesntHave('purchaseRequisitionSources')
                ->orderBy('required_item_id')
                ->lockForUpdate()
                ->get();

            if ($requirements->isEmpty()) {
                throw ValidationException::withMessages(['requirements' => __('procurement.purchase_requisitions.validation.no_missing_requirements')]);
            }

            $requisition = PurchaseRequisition::query()->create([
                'requisition_number' => $this->nextRequisitionNumber(),
                'status' => PurchaseRequisitionStatus::Requested->value,
                'requested_by' => $causer?->id,
                'requested_at' => now(),
                'notes' => __('procurement.purchase_requisitions.notes.generated_from_missing_requirements'),
            ]);

            $requirements
                ->groupBy(fn (MaterialRequirement $requirement): string => "{$requirement->required_item_id}|{$requirement->unit}")
                ->each(function ($group) use ($requisition): void {
                    /** @var MaterialRequirement $first */
                    $first = $group->first();
                    /** @var PurchaseRequisitionItem $item */
                    $item = $requisition->items()->create([
                        'item_id' => $first->required_item_id,
                        'material_requirement_id' => $first->id,
                        'quantity' => $group->sum(fn (MaterialRequirement $requirement): float => (float) $requirement->missing_quantity),
                        'unit' => $first->unit,
                        'status' => PurchaseRequisitionItemStatus::Requested->value,
                    ]);

                    foreach ($group as $requirement) {
                        $item->sources()->create([
                            'material_requirement_id' => $requirement->id,
                            'quantity' => $requirement->missing_quantity,
                        ]);
                    }
                });

            $this->auditLogService->log('purchase_requisition_generated', $requisition, [
                'material_requirements_count' => $requirements->count(),
                'items_count' => $requisition->items()->count(),
            ], $causer);

            return $requisition->refresh();
        });

        $this->cacheInvalidator->procurementChanged();

        return $requisition;
    }

    public function generatePurchaseOrder(PurchaseRequisition $purchaseRequisition, int $supplierId, ?string $expectedDeliveryDate = null, ?User $causer = null): PurchaseOrder
    {
        $purchaseOrder = DB::transaction(function () use ($purchaseRequisition, $supplierId, $expectedDeliveryDate, $causer): PurchaseOrder {
            $purchaseRequisition = PurchaseRequisition::query()
                ->whereKey($purchaseRequisition->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($purchaseRequisition->status !== PurchaseRequisitionStatus::Approved) {
                throw ValidationException::withMessages(['status' => __('procurement.purchase_requisitions.validation.only_approved_generate_po')]);
            }

            $purchaseRequisition->load('items');

            $purchaseOrder = PurchaseOrder::query()->create([
                'order_number' => $this->nextPurchaseOrderNumber(),
                'supplier_id' => $supplierId,
                'purchase_requisition_id' => $purchaseRequisition->id,
                'status' => PurchaseOrderStatus::Draft->value,
                'expected_delivery_date' => $expectedDeliveryDate,
                'created_by' => $causer?->id,
            ]);

            foreach ($purchaseRequisition->items as $item) {
                $purchaseOrder->items()->create([
                    'purchase_requisition_item_id' => $item->id,
                    'item_id' => $item->item_id,
                    'ordered_quantity' => $item->quantity,
                    'received_quantity' => 0,
                    'unit' => $item->unit,
                    'status' => PurchaseOrderItemStatus::Ordered->value,
                    'notes' => $item->notes,
                ]);
            }

            $purchaseRequisition->update(['status' => PurchaseRequisitionStatus::Ordered->value]);
            $purchaseRequisition->items()->update(['status' => PurchaseRequisitionItemStatus::Ordered->value]);

            $this->auditLogService->log('purchase_order_generated', $purchaseOrder, [
                'purchase_requisition_id' => $purchaseRequisition->id,
            ], $causer);

            return $purchaseOrder->refresh();
        });

        $this->cacheInvalidator->procurementChanged();

        return $purchaseOrder;
    }

    private function nextRequisitionNumber(): string
    {
        return 'PR/'.now()->format('Y').'/'.str_pad((string) (PurchaseRequisition::query()->withTrashed()->count() + 1), 4, '0', STR_PAD_LEFT);
    }

    private function nextPurchaseOrderNumber(): string
    {
        return 'PO/'.now()->format('Y').'/'.str_pad((string) (PurchaseOrder::query()->withTrashed()->count() + 1), 4, '0', STR_PAD_LEFT);
    }
}
