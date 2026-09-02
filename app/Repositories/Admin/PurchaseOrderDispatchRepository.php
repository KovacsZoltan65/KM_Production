<?php

namespace App\Repositories\Admin;

use App\Models\PurchaseOrderDispatch;
use App\Repositories\Contracts\PurchaseOrderDispatchRepositoryInterface;

class PurchaseOrderDispatchRepository implements PurchaseOrderDispatchRepositoryInterface
{
    public function findById(int $id): ?PurchaseOrderDispatch
    {
        return PurchaseOrderDispatch::query()->find($id);
    }

    public function findByIdempotencyKey(int $purchaseOrderId, string $key): ?PurchaseOrderDispatch
    {
        return PurchaseOrderDispatch::query()
            ->where('purchase_order_id', $purchaseOrderId)
            ->where('idempotency_key', $key)
            ->first();
    }

    public function latestForPurchaseOrder(int $purchaseOrderId): ?PurchaseOrderDispatch
    {
        return PurchaseOrderDispatch::query()
            ->where('purchase_order_id', $purchaseOrderId)
            ->orderByDesc('dispatch_sequence')
            ->first();
    }

    public function nextSequenceForPurchaseOrder(int $purchaseOrderId): int
    {
        return ((int) PurchaseOrderDispatch::query()
            ->where('purchase_order_id', $purchaseOrderId)
            ->max('dispatch_sequence')) + 1;
    }

    public function create(array $attributes): PurchaseOrderDispatch
    {
        return PurchaseOrderDispatch::query()->create($attributes);
    }
}
