<?php

namespace App\Support\Procurement;

use Illuminate\Contracts\Support\Arrayable;

/** Immutable, non-persisted execution-readiness result for one PR item. */
final readonly class PurchaseRequisitionItemExecutionReadinessResult implements Arrayable
{
    /**
     * @param  list<ExecutionReadinessReason>  $blockingReasons
     * @param  list<ExecutionReadinessReason>  $warnings
     */
    public function __construct(
        public int $purchaseRequisitionItemId,
        public int $itemId,
        public string $itemNumber,
        public ?int $supplierId,
        public ?int $itemSupplierId,
        public array $blockingReasons,
        public array $warnings,
    ) {}

    public function isReady(): bool
    {
        return $this->blockingReasons === [];
    }

    /**
     * @return array{
     *     purchase_requisition_item_id: int,
     *     item_id: int,
     *     item_number: string,
     *     supplier_id: int|null,
     *     item_supplier_id: int|null,
     *     is_ready: bool,
     *     blocking_reasons: list<array<string, mixed>>,
     *     warnings: list<array<string, mixed>>
     * }
     */
    public function toArray(): array
    {
        return [
            'purchase_requisition_item_id' => $this->purchaseRequisitionItemId,
            'item_id' => $this->itemId,
            'item_number' => $this->itemNumber,
            'supplier_id' => $this->supplierId,
            'item_supplier_id' => $this->itemSupplierId,
            'is_ready' => $this->isReady(),
            'blocking_reasons' => array_map(
                fn (ExecutionReadinessReason $reason): array => $reason->toArray(),
                $this->blockingReasons,
            ),
            'warnings' => array_map(
                fn (ExecutionReadinessReason $reason): array => $reason->toArray(),
                $this->warnings,
            ),
        ];
    }
}
