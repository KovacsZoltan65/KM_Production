<?php

namespace App\Models;

use App\Enums\MerlinRunStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Internal storage only. Historical actor identity grants no authorization.
 * Future services own admission, fresh authorization, transitions and lawful deletion.
 *
 * @property string $id
 * @property string $problem_case_id
 * @property int|null $initiating_user_id
 * @property int $historical_actor_id
 * @property int $starting_evaluation_id
 * @property string $canonical_request
 * @property MerlinRunStatus $status
 * @property array<string, mixed> $configuration
 * @property array<string, mixed>|null $result
 */
#[Fillable([
    'problem_case_id', 'initiating_user_id', 'historical_actor_id', 'starting_evaluation_id',
    'status', 'stop_reason', 'idempotency_key', 'request_schema_version', 'canonical_request',
    'fingerprint_version', 'request_fingerprint', 'configuration_schema_version', 'configuration',
    'result_schema_version', 'result', 'started_at', 'completed_at',
    'cancellation_requested_at', 'cancelled_at',
])]
class MerlinRun extends Model
{
    use HasUuids;

    protected static function booted(): void
    {
        static::creating(function (self $run): void {
            if ($run->initiating_user_id !== null && $run->initiating_user_id !== $run->historical_actor_id) {
                throw new LogicException('MerlinRun live and historical initiating actor must agree.');
            }
        });

        static::updating(function (self $run): void {
            if ($run->isDirty([
                'id', 'problem_case_id', 'initiating_user_id', 'historical_actor_id', 'starting_evaluation_id',
                'idempotency_key', 'request_schema_version', 'canonical_request',
                'fingerprint_version', 'request_fingerprint', 'configuration_schema_version', 'configuration',
            ])) {
                throw new LogicException('MerlinRun binding and admission identity are immutable.');
            }

            $originalStatus = MerlinRunStatus::from($run->getRawOriginal('status'));
            if ($originalStatus->isTerminal() && $run->isDirty([
                'status', 'stop_reason', 'started_at', 'completed_at', 'cancelled_at',
                'cancellation_requested_at', 'result_schema_version', 'result',
            ])) {
                throw new LogicException('MerlinRun terminal outcome is immutable.');
            }
        });
    }

    /** @return BelongsTo<ProblemCase, $this> */
    public function problemCase(): BelongsTo
    {
        return $this->belongsTo(ProblemCase::class);
    }

    /** @return BelongsTo<User, $this> */
    public function initiatingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiating_user_id');
    }

    /** @return BelongsTo<ProblemCaseEvaluation, $this> */
    public function startingEvaluation(): BelongsTo
    {
        return $this->belongsTo(ProblemCaseEvaluation::class, 'starting_evaluation_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => MerlinRunStatus::class,
            'historical_actor_id' => 'integer',
            'starting_evaluation_id' => 'integer',
            'initiating_user_id' => 'integer',
            'request_schema_version' => 'integer',
            'fingerprint_version' => 'integer',
            'configuration_schema_version' => 'integer',
            'result_schema_version' => 'integer',
            'configuration' => 'array',
            'result' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancellation_requested_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }
}
