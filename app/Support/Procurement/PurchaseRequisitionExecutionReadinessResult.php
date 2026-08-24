<?php

namespace App\Support\Procurement;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;

/** Immutable, current-state result; it is never persisted as a forever-ready flag. */
final readonly class PurchaseRequisitionExecutionReadinessResult implements Arrayable
{
    /**
     * @param  list<ExecutionReadinessReason>  $blockingReasons
     * @param  list<ExecutionReadinessReason>  $warnings
     * @param  list<PurchaseRequisitionItemExecutionReadinessResult>  $itemResults
     */
    public function __construct(
        public int $purchaseRequisitionId,
        public array $blockingReasons,
        public array $warnings,
        public array $itemResults,
        public Carbon $checkedAt,
    ) {}

    public function isReady(): bool
    {
        return $this->blockingReasons === [];
    }

    /**
     * @return array{
     *     purchase_requisition_id: int,
     *     is_ready: bool,
     *     blocking_reasons: list<array<string, mixed>>,
     *     warnings: list<array<string, mixed>>,
     *     item_results: list<array<string, mixed>>,
     *     checked_at: string
     * }
     */
    public function toArray(): array
    {
        return [
            'purchase_requisition_id' => $this->purchaseRequisitionId,
            'is_ready' => $this->isReady(),
            'blocking_reasons' => array_map(
                fn (ExecutionReadinessReason $reason): array => $reason->toArray(),
                $this->blockingReasons,
            ),
            'warnings' => array_map(
                fn (ExecutionReadinessReason $reason): array => $reason->toArray(),
                $this->warnings,
            ),
            'item_results' => array_map(
                fn (PurchaseRequisitionItemExecutionReadinessResult $result): array => $result->toArray(),
                $this->itemResults,
            ),
            'checked_at' => $this->checkedAt->toISOString(),
        ];
    }
}
