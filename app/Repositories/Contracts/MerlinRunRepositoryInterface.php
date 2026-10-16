<?php

namespace App\Repositories\Contracts;

use App\Models\MerlinRun;
use App\Models\ProblemCaseEvaluation;
use App\Support\Merlin\MaterialShortageCurrentEvaluation;
use App\Support\Merlin\MerlinRunConfiguration;
use App\Support\Merlin\StartInvestigationRequest;
use Illuminate\Support\Carbon;

interface MerlinRunRepositoryInterface
{
    public function findIdentity(string $connection, int $actorId, StartInvestigationRequest $request): ?MerlinRun;

    public function hasActive(string $connection, string $caseId): bool;

    public function appendStarting(string $connection, MaterialShortageCurrentEvaluation $evaluation, Carbon $observedAt): ProblemCaseEvaluation;

    public function createPending(string $connection, int $actorId, StartInvestigationRequest $request, int $evaluationId, MerlinRunConfiguration $configuration, Carbon $createdAt): MerlinRun;
}
