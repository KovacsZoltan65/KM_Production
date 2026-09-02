<?php

namespace App\Repositories\Contracts;

use App\Models\PurchaseOrderDispatch;

interface PurchaseOrderDispatchRepositoryInterface
{
    public function findById(int $id): ?PurchaseOrderDispatch;

    public function findByIdempotencyKey(int $purchaseOrderId, string $key): ?PurchaseOrderDispatch;

    public function latestForPurchaseOrder(int $purchaseOrderId): ?PurchaseOrderDispatch;

    public function nextSequenceForPurchaseOrder(int $purchaseOrderId): int;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): PurchaseOrderDispatch;
}
