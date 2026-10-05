<?php

namespace App\Repositories\Admin;

use App\Enums\ProblemCaseEvaluationResult;
use App\Enums\ProblemCaseLifecycle;
use App\Enums\ProblemCaseType;
use App\Models\ProblemCase;
use App\Repositories\Contracts\ProblemCaseRepositoryInterface;
use App\Support\Merlin\MaterialShortageDetectionSnapshot;
use Illuminate\Support\Facades\DB;

/**
 * Internal persistence only: no detection, netting, resolver, authorization or transitions.
 * A future authorized backend producer must establish business validity before calling this.
 */
class ProblemCaseRepository implements ProblemCaseRepositoryInterface
{
    public function findForCurrentEvaluation(string $problemCaseId): ProblemCase
    {
        return ProblemCase::query()->with([
            'materialRequirement.customerOrderItem' => fn ($query) => $query->withTrashed(),
            'materialRequirement.customerOrderItem.customerOrder' => fn ($query) => $query->withTrashed(),
            'materialRequirement.productionOrder' => fn ($query) => $query->withTrashed(),
            'materialRequirement.bomItem',
        ])->findOrFail($problemCaseId);
    }

    public function createMaterialShortage(
        MaterialShortageDetectionSnapshot $snapshot,
        ProblemCaseEvaluationResult $initialEvaluation,
    ): ProblemCase {
        // This transaction is a single aggregate persistence operation, not a domain workflow.
        return DB::transaction(function () use ($snapshot, $initialEvaluation): ProblemCase {
            $evaluatedAt = $snapshot->detectedAt->utc();

            $case = ProblemCase::query()->create([
                'type' => ProblemCaseType::MaterialShortage,
                'material_requirement_id' => $snapshot->netting->requirementId,
                'lifecycle' => ProblemCaseLifecycle::Open,
                'detection_snapshot' => $snapshot->toArray(),
                'current_evaluation' => $initialEvaluation,
                'current_evaluated_at' => $evaluatedAt,
                'current_evaluation_evidence' => $snapshot->evaluationEvidence(),
            ]);

            $case->evaluations()->create([
                'result' => $initialEvaluation,
                'evaluated_at' => $evaluatedAt,
                'recorded_for' => 'case_creation',
                'evidence' => $snapshot->evaluationEvidence(),
            ]);

            return $case;
        });
    }
}
