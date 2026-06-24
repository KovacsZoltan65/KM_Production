<?php

namespace App\Services\Admin;

use App\Enums\GoodsReceiptStatus;
use App\Enums\PurchaseOrderItemStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementType;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Repositories\Contracts\GoodsReceiptRepositoryInterface;
use App\Repositories\Contracts\StockBalanceRepositoryInterface;
use App\Services\AuditLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GoodsReceiptService
{
    public function __construct(
        private readonly GoodsReceiptRepositoryInterface $goodsReceipts,
        private readonly StockBalanceRepositoryInterface $stockBalances,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->goodsReceipts->paginateForAdminIndex($filters, $perPage);
    }

    public function findForShow(GoodsReceipt $goodsReceipt): GoodsReceipt
    {
        return $this->goodsReceipts->findForShow($goodsReceipt);
    }

    public function create(array $attributes, ?User $causer = null): GoodsReceipt
    {
        return DB::transaction(function () use ($attributes, $causer): GoodsReceipt {
            $items = $attributes['items'] ?? [];
            unset($attributes['items']);

            $goodsReceipt = GoodsReceipt::query()->create([
                'receipt_number' => $attributes['receipt_number'] ?? $this->nextReceiptNumber(),
                'purchase_order_id' => $attributes['purchase_order_id'] ?? null,
                'status' => GoodsReceiptStatus::Draft->value,
                'received_by' => $causer?->id,
                'received_at' => $attributes['received_at'] ?? now(),
                'notes' => $attributes['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                $goodsReceipt->items()->create([
                    'purchase_order_item_id' => $item['purchase_order_item_id'] ?? null,
                    'item_id' => $item['item_id'],
                    'item_batch_id' => $item['item_batch_id'] ?? null,
                    'location_id' => $item['location_id'],
                    'quantity' => $item['quantity'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $this->auditLogService->log('goods_receipt_created', $goodsReceipt, [
                'items_count' => count($items),
            ], $causer);

            return $goodsReceipt->refresh();
        });
    }

    public function post(GoodsReceipt $goodsReceipt, ?User $causer = null): GoodsReceipt
    {
        if ($goodsReceipt->status === GoodsReceiptStatus::Posted) {
            throw ValidationException::withMessages(['status' => 'Posted goods receipts cannot be posted again.']);
        }

        return DB::transaction(function () use ($goodsReceipt, $causer): GoodsReceipt {
            $goodsReceipt->load(['items', 'purchaseOrder.items']);

            foreach ($goodsReceipt->items as $item) {
                $this->stockBalances->increaseQuantity(
                    $item->item_id,
                    $item->location_id,
                    $item->item_batch_id,
                    (float) $item->quantity
                );

                $movement = StockMovement::query()->create([
                    'item_id' => $item->item_id,
                    'item_batch_id' => $item->item_batch_id,
                    'to_location_id' => $item->location_id,
                    'quantity' => $item->quantity,
                    'movement_type' => StockMovementType::PurchaseReceive->value,
                    'source_type' => GoodsReceipt::class,
                    'source_id' => $goodsReceipt->id,
                    'performed_by' => $causer?->id,
                    'performed_at' => now(),
                    'notes' => 'Goods receipt posted.',
                ]);

                $this->auditLogService->log('stock_inbound_created', $movement, [
                    'goods_receipt_id' => $goodsReceipt->id,
                    'goods_receipt_item_id' => $item->id,
                ], $causer);

                if ($item->purchase_order_item_id !== null) {
                    $this->updatePurchaseOrderItemReceivedQuantity($item->purchaseOrderItem, (float) $item->quantity);
                }
            }

            $goodsReceipt->update(['status' => GoodsReceiptStatus::Posted->value]);
            $this->refreshPurchaseOrderStatus($goodsReceipt);

            $this->auditLogService->log('goods_receipt_posted', $goodsReceipt, [
                'items_count' => $goodsReceipt->items->count(),
            ], $causer);

            return $goodsReceipt->refresh();
        });
    }

    private function updatePurchaseOrderItemReceivedQuantity(?PurchaseOrderItem $purchaseOrderItem, float $quantity): void
    {
        if ($purchaseOrderItem === null) {
            return;
        }

        $receivedQuantity = (float) $purchaseOrderItem->received_quantity + $quantity;
        $orderedQuantity = (float) $purchaseOrderItem->ordered_quantity;

        $purchaseOrderItem->update([
            'received_quantity' => $receivedQuantity,
            'status' => $receivedQuantity >= $orderedQuantity
                ? PurchaseOrderItemStatus::Received->value
                : PurchaseOrderItemStatus::PartiallyReceived->value,
        ]);
    }

    private function refreshPurchaseOrderStatus(GoodsReceipt $goodsReceipt): void
    {
        $purchaseOrder = $goodsReceipt->purchaseOrder;

        if ($purchaseOrder === null) {
            return;
        }

        $purchaseOrder->load('items');
        $allReceived = $purchaseOrder->items->every(fn (PurchaseOrderItem $item): bool => $item->status === PurchaseOrderItemStatus::Received);
        $anyReceived = $purchaseOrder->items->contains(fn (PurchaseOrderItem $item): bool => (float) $item->received_quantity > 0);

        $purchaseOrder->update([
            'status' => $allReceived
                ? PurchaseOrderStatus::Received->value
                : ($anyReceived ? PurchaseOrderStatus::PartiallyReceived->value : $purchaseOrder->status->value),
        ]);
    }

    private function nextReceiptNumber(): string
    {
        return 'GR/'.now()->format('Y').'/'.str_pad((string) (GoodsReceipt::query()->withTrashed()->count() + 1), 4, '0', STR_PAD_LEFT);
    }
}
