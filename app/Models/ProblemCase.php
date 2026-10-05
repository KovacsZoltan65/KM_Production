<?php

namespace App\Models;

use App\Enums\ProblemCaseEvaluationResult;
use App\Enums\ProblemCaseLifecycle;
use App\Enums\ProblemCaseType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Persistence foundation only; a case grants no permissions.
 *
 * @property string $id
 * @property ProblemCaseType $type
 * @property int $material_requirement_id
 * @property ProblemCaseLifecycle $lifecycle
 * @property array<string, mixed> $detection_snapshot
 * @property ProblemCaseEvaluationResult $current_evaluation
 * @property Carbon $current_evaluated_at
 * @property array<string, mixed> $current_evaluation_evidence
 * @property-read MaterialRequirement|null $materialRequirement
 */
#[Fillable([
    'type', 'material_requirement_id', 'lifecycle', 'detection_snapshot',
    'current_evaluation', 'current_evaluated_at', 'current_evaluation_evidence',
])]
class ProblemCase extends Model
{
    use HasUuids;

    protected static function booted(): void
    {
        static::updating(function (self $case): void {
            if ($case->isDirty(['id', 'type', 'material_requirement_id', 'detection_snapshot'])) {
                throw new LogicException('Problem Case identity and detection evidence are immutable.');
            }
        });

        static::deleting(function (): void {
            throw new LogicException('Problem Case history must be preserved.');
        });
    }

    /** @return BelongsTo<MaterialRequirement, $this> */
    public function materialRequirement(): BelongsTo
    {
        return $this->belongsTo(MaterialRequirement::class)->withTrashed();
    }

    /** @return HasMany<ProblemCaseEvaluation, $this> */
    public function evaluations(): HasMany
    {
        return $this->hasMany(ProblemCaseEvaluation::class)->orderBy('id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => ProblemCaseType::class,
            'lifecycle' => ProblemCaseLifecycle::class,
            'detection_snapshot' => 'array',
            'current_evaluation' => ProblemCaseEvaluationResult::class,
            'current_evaluated_at' => 'datetime',
            'current_evaluation_evidence' => 'array',
        ];
    }
}
