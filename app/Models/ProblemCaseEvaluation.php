<?php

namespace App\Models;

use App\Enums\ProblemCaseEvaluationResult;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Append-only evidence, distinct from the current evaluation projection.
 *
 * @property int $id
 * @property string $problem_case_id
 * @property ProblemCaseEvaluationResult $result
 * @property Carbon $evaluated_at
 * @property string $recorded_for
 * @property array<string, mixed> $evidence
 */
#[Fillable(['problem_case_id', 'result', 'evaluated_at', 'recorded_for', 'evidence'])]
class ProblemCaseEvaluation extends Model
{
    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Problem Case evaluation history is append-only.');
        });

        static::deleting(function (): void {
            throw new LogicException('Problem Case evaluation history is append-only.');
        });
    }

    /** @return BelongsTo<ProblemCase, $this> */
    public function problemCase(): BelongsTo
    {
        return $this->belongsTo(ProblemCase::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'result' => ProblemCaseEvaluationResult::class,
            'evaluated_at' => 'datetime',
            'evidence' => 'array',
        ];
    }
}
