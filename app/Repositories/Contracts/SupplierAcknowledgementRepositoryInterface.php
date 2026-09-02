<?php

namespace App\Repositories\Contracts;

use App\Models\SupplierAcknowledgement;

interface SupplierAcknowledgementRepositoryInterface
{
    public function findByIdempotencyKey(int $purchaseOrderId, string $key): ?SupplierAcknowledgement;

    public function findByResponseFingerprint(int $purchaseOrderId, string $fingerprint): ?SupplierAcknowledgement;

    public function effectiveForPurchaseOrder(int $purchaseOrderId): ?SupplierAcknowledgement;

    public function latestForPurchaseOrder(int $purchaseOrderId): ?SupplierAcknowledgement;

    public function nextSequenceForPurchaseOrder(int $purchaseOrderId): int;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): SupplierAcknowledgement;

    /** @param list<array{purchase_order_item_id: int}> $rows */
    public function createScopeItems(SupplierAcknowledgement $acknowledgement, array $rows): void;

    /** @param list<array<string, mixed>> $rows */
    public function createItems(SupplierAcknowledgement $acknowledgement, array $rows): void;
}
