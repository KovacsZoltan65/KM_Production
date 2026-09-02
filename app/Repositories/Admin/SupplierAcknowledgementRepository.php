<?php

namespace App\Repositories\Admin;

use App\Models\SupplierAcknowledgement;
use App\Repositories\Contracts\SupplierAcknowledgementRepositoryInterface;

class SupplierAcknowledgementRepository implements SupplierAcknowledgementRepositoryInterface
{
    public function findByIdempotencyKey(int $purchaseOrderId, string $key): ?SupplierAcknowledgement
    {
        return SupplierAcknowledgement::query()
            ->with('scopeItems')
            ->where('purchase_order_id', $purchaseOrderId)
            ->where('idempotency_key', $key)
            ->first();
    }

    public function findByResponseFingerprint(int $purchaseOrderId, string $fingerprint): ?SupplierAcknowledgement
    {
        return SupplierAcknowledgement::query()
            ->where('purchase_order_id', $purchaseOrderId)
            ->where('response_fingerprint', $fingerprint)
            ->first();
    }

    public function effectiveForPurchaseOrder(int $purchaseOrderId): ?SupplierAcknowledgement
    {
        return SupplierAcknowledgement::query()
            ->with(['items', 'scopeItems'])
            ->where('purchase_order_id', $purchaseOrderId)
            ->whereDoesntHave('successor')
            ->first();
    }

    public function latestForPurchaseOrder(int $purchaseOrderId): ?SupplierAcknowledgement
    {
        return SupplierAcknowledgement::query()
            ->where('purchase_order_id', $purchaseOrderId)
            ->orderByDesc('acknowledgement_sequence')
            ->first();
    }

    public function nextSequenceForPurchaseOrder(int $purchaseOrderId): int
    {
        return ((int) SupplierAcknowledgement::query()
            ->where('purchase_order_id', $purchaseOrderId)
            ->max('acknowledgement_sequence')) + 1;
    }

    public function create(array $attributes): SupplierAcknowledgement
    {
        return SupplierAcknowledgement::query()->create($attributes);
    }

    public function createScopeItems(SupplierAcknowledgement $acknowledgement, array $rows): void
    {
        $acknowledgement->scopeItems()->createMany($rows);
    }

    public function createItems(SupplierAcknowledgement $acknowledgement, array $rows): void
    {
        $acknowledgement->items()->createMany($rows);
    }
}
