<?php

namespace App\Support\Merlin;

use App\Enums\MaterialRequirementSourceValidity;
use App\Enums\ProblemCaseEvaluationResult;
use App\Support\MaterialPlanning\MaterialRequirementNettingResult;

/** Read-only result; never a replacement for the persisted evaluation projection. */
final readonly class MaterialShortageCurrentEvaluation
{
    public function __construct(
        public string $problemCaseId,
        public int $materialRequirementId,
        public MaterialRequirementSourceValidity $sourceValidity,
        public ?ProblemCaseEvaluationResult $evaluation,
        public bool $sourceValidityAuthoritative,
        public string $reason,
        public bool $legacyProductionLineage = false,
        public ?MaterialRequirementNettingResult $netting = null,
    ) {}

    public function netRequirement(): ?string
    {
        return $this->netting?->netRequirement;
    }

    public function nettingAuthoritative(): bool
    {
        return $this->netting !== null;
    }

    public function invalidationCandidate(): bool
    {
        return $this->sourceValidity === MaterialRequirementSourceValidity::Invalid;
    }
}
