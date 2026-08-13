<?php

namespace App\Support\MaterialPlanning;

/** Immutable, non-persisted requirement-level netting calculation result. */
final readonly class MaterialRequirementNettingResult
{
    public function __construct(
        public int $requirementId,
        public ?int $productionOrderId,
        public ?int $bomItemId,
        public int $requiredItemId,
        public ?string $requiredAt,
        public string $unit,
        public string $grossRequirement,
        public string $onHandCoverage,
        public string $incomingCoverage,
        public string $netRequirement,
    ) {}

    /** @return array<string, int|string|null> */
    public function toArray(): array
    {
        return [
            'requirement_id' => $this->requirementId,
            'production_order_id' => $this->productionOrderId,
            'bom_item_id' => $this->bomItemId,
            'required_item_id' => $this->requiredItemId,
            'required_at' => $this->requiredAt,
            'unit' => $this->unit,
            'gross_requirement' => $this->grossRequirement,
            'on_hand_coverage' => $this->onHandCoverage,
            'incoming_coverage' => $this->incomingCoverage,
            'net_requirement' => $this->netRequirement,
        ];
    }
}
