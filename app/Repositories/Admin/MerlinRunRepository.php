<?php

namespace App\Repositories\Admin;

use App\Enums\MerlinRunStatus;
use App\Models\MerlinRun;
use App\Models\ProblemCaseEvaluation;
use App\Repositories\Contracts\MerlinRunRepositoryInterface;
use App\Support\Merlin\MaterialShortageCurrentEvaluation;
use App\Support\Merlin\MerlinRunConfiguration;
use App\Support\Merlin\StartInvestigationRequest;
use Illuminate\Support\Carbon;
use LogicException;

class MerlinRunRepository implements MerlinRunRepositoryInterface
{
    public function findIdentity(string $connection, int $actorId, StartInvestigationRequest $request): ?MerlinRun
    {
        return MerlinRun::on($connection)->where('historical_actor_id', $actorId)
            ->where('problem_case_id', $request->problemCaseId)->where('idempotency_key', $request->idempotencyKey)
            ->lockForUpdate()->first();
    }

    public function hasActive(string $connection, string $caseId): bool
    {
        return MerlinRun::on($connection)->where('problem_case_id', $caseId)
            ->whereIn('status', [MerlinRunStatus::Pending->value, MerlinRunStatus::Running->value])
            ->lockForUpdate()->first(['id']) !== null;
    }

    public function appendStarting(string $connection, MaterialShortageCurrentEvaluation $evaluation, Carbon $observedAt): ProblemCaseEvaluation
    {
        if ($evaluation->evaluation === null || $evaluation->netting === null) {
            throw new LogicException('Starting observation requires authoritative evaluation and netting.');
        }

        return ProblemCaseEvaluation::on($connection)->create([
            'problem_case_id' => $evaluation->problemCaseId,
            'result' => $evaluation->evaluation,
            'evaluated_at' => $observedAt,
            'recorded_for' => 'merlin_investigation_start',
            'evidence' => [
                'schema_version' => 1, 'origin' => 'material_shortage_current_resolver',
                'material_requirement_id' => $evaluation->materialRequirementId,
                'source_validity' => $evaluation->sourceValidity->value,
                'source_validity_authoritative' => $evaluation->sourceValidityAuthoritative,
                'reason' => $evaluation->reason, 'legacy_production_lineage' => $evaluation->legacyProductionLineage,
                'netting' => $evaluation->netting->toArray(),
            ],
        ]);
    }

    public function createPending(string $connection, int $actorId, StartInvestigationRequest $request, int $evaluationId, MerlinRunConfiguration $configuration, Carbon $createdAt): MerlinRun
    {
        return MerlinRun::on($connection)->create([
            ...$request->persistenceAttributes(), ...$configuration->persistenceAttributes(),
            'initiating_user_id' => $actorId, 'historical_actor_id' => $actorId,
            'starting_evaluation_id' => $evaluationId, 'status' => MerlinRunStatus::Pending,
            'created_at' => $createdAt, 'updated_at' => $createdAt,
        ]);
    }
}
