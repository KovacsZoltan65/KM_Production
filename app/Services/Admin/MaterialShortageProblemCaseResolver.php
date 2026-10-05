<?php

namespace App\Services\Admin;

use App\Enums\MaterialRequirementSourceValidity as Validity;
use App\Enums\ProblemCaseEvaluationResult as Evaluation;
use App\Enums\ProblemCaseLifecycle;
use App\Repositories\Contracts\ProblemCaseRepositoryInterface;
use App\Support\Merlin\MaterialShortageCurrentEvaluation;
use LogicException;

final class MaterialShortageProblemCaseResolver
{
    public function __construct(
        private readonly ProblemCaseRepositoryInterface $cases,
        private readonly MaterialRequirementDemandEligibilityService $eligibility,
        private readonly MaterialRequirementNettingService $netting,
    ) {}

    public function resolve(string $problemCaseId): MaterialShortageCurrentEvaluation
    {
        $case = $this->cases->findForCurrentEvaluation($problemCaseId);
        if ($case->lifecycle !== ProblemCaseLifecycle::Open) {
            throw new LogicException('Current material shortage evaluation requires an open material shortage case.');
        }

        $requirement = $case->materialRequirement;
        if ($requirement === null) {
            return new MaterialShortageCurrentEvaluation(
                $case->id, $case->material_requirement_id, Validity::Undetermined,
                Evaluation::Undetermined, false, 'material_requirement_unavailable',
            );
        }

        $source = $this->eligibility->assessSource($requirement);
        if ($source->validity !== Validity::Valid) {
            return new MaterialShortageCurrentEvaluation(
                $case->id, $requirement->id, $source->validity,
                $source->validity === Validity::Invalid ? null : Evaluation::Undetermined,
                $source->validity === Validity::Invalid, $source->reason,
            );
        }

        // Use normal MRP's entire eligible competing scope, then select the case requirement.
        $selected = $this->netting->calculate()->firstWhere('requirementId', $requirement->id);

        return new MaterialShortageCurrentEvaluation(
            $case->id, $requirement->id, Validity::Valid,
            $selected === null ? Evaluation::Undetermined
                : ($selected->netRequirement === '0.000' ? Evaluation::Resolved : Evaluation::Active),
            true, $selected === null ? 'selected_netting_result_unavailable' : $source->reason,
            $source->legacyProductionLineage, $selected,
        );
    }
}
