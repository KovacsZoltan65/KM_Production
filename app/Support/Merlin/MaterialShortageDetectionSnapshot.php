<?php

namespace App\Support\Merlin;

use App\Support\MaterialPlanning\MaterialRequirementNettingResult;
use Carbon\CarbonImmutable;

/** Supplied by a trusted backend producer; this DTO does not prove source validity or scope completeness. */
final readonly class MaterialShortageDetectionSnapshot
{
    public function __construct(
        public MaterialRequirementNettingResult $netting,
        public int $customerOrderItemId,
        public ?int $customerOrderId,
        public string $itemIdentifier,
        public string $itemName,
        public CarbonImmutable $detectedAt,
        public string $detectionSource,
        /** @var array<string, mixed> Calculation source/rule version metadata; no invented netting-run ID. */
        public array $nettingProvenance,
        /** @var array<string, mixed> Actual competing scope, including the requirement IDs used. */
        public array $nettingScope,
        /** @var array<string, mixed> Additional compact calculation evidence. */
        public array $evidence,
    ) {}

    /** @return array<string, mixed> */
    public function evaluationEvidence(): array
    {
        return [
            'netting' => [...$this->netting->toArray(), 'allocations' => $this->netting->allocations],
            'source' => $this->detectionSource,
            'netting_provenance' => $this->nettingProvenance,
            'netting_scope' => $this->nettingScope,
            'evidence' => $this->evidence,
        ];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema_version' => 1,
            'customer_order_item_id' => $this->customerOrderItemId,
            'customer_order_id' => $this->customerOrderId,
            'item_identifier' => $this->itemIdentifier,
            'item_name' => $this->itemName,
            'detected_at' => $this->detectedAt->utc()->toIso8601String(),
            ...$this->evaluationEvidence(),
        ];
    }
}
