<?php

namespace App\Support\Procurement;

/** Immutable summary of one atomic proposal-consolidation batch. */
final readonly class PurchaseRequisitionConsolidationResult
{
    /** @param list<int> $createdRequisitionIds */
    public function __construct(
        public array $createdRequisitionIds,
        public int $proposalCount,
        public int $itemCount,
        public int $sourceCount,
    ) {}

    public function requisitionCount(): int
    {
        return count($this->createdRequisitionIds);
    }
}
