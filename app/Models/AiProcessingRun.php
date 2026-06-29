<?php

namespace App\Models;

use App\Enums\AiProcessingRunStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $processable_type
 * @property int|null $processable_id
 * @property string $task
 * @property string|null $engine
 * @property string|null $engine_version
 * @property string|null $backend
 * @property AiProcessingRunStatus $status
 * @property bool $success
 * @property float|null $confidence
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property int|null $duration_ms
 * @property string|null $error_code
 * @property string|null $error_message
 * @property array<string, mixed>|null $metadata
 * @property array<string, mixed>|null $result_summary
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Model|null $processable
 */
#[Fillable([
    'processable_type',
    'processable_id',
    'task',
    'engine',
    'engine_version',
    'backend',
    'status',
    'success',
    'confidence',
    'started_at',
    'finished_at',
    'duration_ms',
    'error_code',
    'error_message',
    'metadata',
    'result_summary',
])]
class AiProcessingRun extends Model
{
    /**
     * @return MorphTo<Model, $this>
     */
    public function processable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isFinished(): bool
    {
        return $this->finished_at !== null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AiProcessingRunStatus::class,
            'success' => 'boolean',
            'confidence' => 'float',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'duration_ms' => 'integer',
            'metadata' => 'array',
            'result_summary' => 'array',
        ];
    }
}
