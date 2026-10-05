<?php

namespace App\Support\MaterialPlanning;

use App\Enums\MaterialRequirementSourceValidity;

final readonly class MaterialRequirementSourceAssessment
{
    public function __construct(
        public MaterialRequirementSourceValidity $validity,
        public string $reason,
        public bool $legacyProductionLineage = false,
    ) {}
}
