<?php

namespace App\Services\Admin;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use App\Services\AuditLogService;
use App\Services\BusinessCacheInvalidator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A beszerzési rendelések életciklusát és auditált állapotváltásait koordinálja.
 *
 * A részletes lekérdezéseket repository-ra delegálja; készletkönyvelést nem végez.
 */
class PurchaseOrderService
{
    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $purchaseOrders,
        private readonly AuditLogService $auditLogService,
        private readonly BusinessCacheInvalidator $cacheInvalidator,
    ) {}

    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->purchaseOrders->paginateForAdminIndex($filters, $perPage);
    }

    public function findForShow(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        return $this->purchaseOrders->findForShow($purchaseOrder);
    }

    public function create(array $attributes, ?User $causer = null): PurchaseOrder
    {
        $purchaseOrder = DB::transaction(function () use ($attributes, $causer): PurchaseOrder {
            $items = $attributes['items'] ?? [];
            unset($attributes['items']);

            $purchaseOrder = PurchaseOrder::query()->create([
                'order_number' => $attributes['order_number'] ?? $this->nextPurchaseOrderNumber(),
                'supplier_id' => $attributes['supplier_id'],
                'purchase_requisition_id' => $attributes['purchase_requisition_id'] ?? null,
                'status' => $attributes['status'] ?? PurchaseOrderStatus::Draft->value,
                'ordered_at' => $attributes['ordered_at'] ?? null,
                'expected_delivery_date' => $attributes['expected_delivery_date'] ?? null,
                'notes' => $attributes['notes'] ?? null,
                'created_by' => $causer?->id,
            ]);

            foreach ($items as $item) {
                $purchaseOrder->items()->create([
                    'purchase_requisition_item_id' => $item['purchase_requisition_item_id'] ?? null,
                    'item_id' => $item['item_id'],
                    'ordered_quantity' => $item['ordered_quantity'],
                    'received_quantity' => $item['received_quantity'] ?? 0,
                    'unit' => $item['unit'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $this->auditLogService->logCreated('purchase_order_created', $purchaseOrder, $causer, [
                'items_count' => \count($items),
            ]);

            return $purchaseOrder->refresh();
        });

        $this->cacheInvalidator->procurementChanged();

        return $purchaseOrder;
    }

    public function update(PurchaseOrder $purchaseOrder, array $attributes, ?User $causer = null): PurchaseOrder
    {
        $purchaseOrder = DB::transaction(function () use ($purchaseOrder, $attributes, $causer): PurchaseOrder {
            $original = $purchaseOrder->getRawOriginal();
            $purchaseOrder->update([
                'supplier_id' => $attributes['supplier_id'] ?? $purchaseOrder->supplier_id,
                'expected_delivery_date' => $attributes['expected_delivery_date'] ?? $purchaseOrder->expected_delivery_date,
                'notes' => $attributes['notes'] ?? $purchaseOrder->notes,
            ]);
            $this->auditLogService->logUpdated('purchase_order_updated', $purchaseOrder, $original, $causer);

            return $purchaseOrder->refresh();
        });
        $this->cacheInvalidator->procurementChanged();

        return $purchaseOrder;
    }

    public function delete(PurchaseOrder $purchaseOrder, ?User $causer = null): void
    {
        $this->auditLogService->log('purchase_order_deleted', $purchaseOrder, [], $causer);
        $purchaseOrder->delete();
        $this->cacheInvalidator->procurementChanged();
    }

    public function approve(PurchaseOrder $purchaseOrder, ?User $causer = null): PurchaseOrder
    {
        if ($purchaseOrder->status !== PurchaseOrderStatus::Draft) {
            throw ValidationException::withMessages(['status' => __('procurement.purchase_orders.validation.only_draft_approve')]);
        }

        $purchaseOrder = DB::transaction(function () use ($purchaseOrder, $causer): PurchaseOrder {
            $original = $purchaseOrder->getRawOriginal();
            $purchaseOrder->update([
                'status' => PurchaseOrderStatus::Ordered->value,
                'ordered_at' => now(),
            ]);
            $this->auditLogService->logUpdated('purchase_order_approved', $purchaseOrder, $original, $causer);

            return $purchaseOrder->refresh();
        });
        $this->cacheInvalidator->procurementChanged();

        return $purchaseOrder;
    }

    public function close(PurchaseOrder $purchaseOrder, ?User $causer = null): PurchaseOrder
    {
        if (! \in_array($purchaseOrder->status, [PurchaseOrderStatus::Ordered, PurchaseOrderStatus::PartiallyReceived], true)) {
            throw ValidationException::withMessages([
                'status' => __('procurement.purchase_orders.validation.only_open_close'),
            ]);
        }

        $purchaseOrder = DB::transaction(function () use ($purchaseOrder, $causer): PurchaseOrder {
            $original = $purchaseOrder->getRawOriginal();
            $purchaseOrder->update(['status' => PurchaseOrderStatus::Received->value]);
            $this->auditLogService->logUpdated('purchase_order_closed', $purchaseOrder, $original, $causer);

            return $purchaseOrder->refresh();
        });
        $this->cacheInvalidator->procurementChanged();

        return $purchaseOrder;
    }

    private function nextPurchaseOrderNumber(): string
    {
        return 'PO/'.now()->format('Y').'/'.str_pad((string) (PurchaseOrder::query()->withTrashed()->count() + 1), 4, '0', STR_PAD_LEFT);
    }
}
