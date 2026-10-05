<?php

namespace App\Repositories\Contracts;

use App\Enums\ProblemCaseEvaluationResult;
use App\Models\ProblemCase;
use App\Support\Merlin\MaterialShortageDetectionSnapshot;

interface ProblemCaseRepositoryInterface
{
    public function findForCurrentEvaluation(string $problemCaseId): ProblemCase;

    public function createMaterialShortage(
        MaterialShortageDetectionSnapshot $snapshot,
        ProblemCaseEvaluationResult $initialEvaluation,
    ): ProblemCase;
}
