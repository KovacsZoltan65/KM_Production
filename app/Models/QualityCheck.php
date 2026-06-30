<?php

namespace App\Models;

use App\Enums\QualityCheckResult;
use Database\Factories\QualityCheckFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $production_task_id
 * @property int $checked_by
 * @property QualityCheckResult $result
 * @property Carbon $checked_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Employee|null $inspector
 * @property-read ProductionTask $productionTask
 *
 * @method static \Database\Factories\QualityCheckFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityCheck newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityCheck newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityCheck query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityCheck whereCheckedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityCheck whereCheckedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityCheck whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityCheck whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityCheck whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityCheck whereProductionTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityCheck whereResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityCheck whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'production_task_id',
    'checked_by',
    'result',
    'checked_at',
    'notes',
])]
class QualityCheck extends Model
{
    /** @use HasFactory<QualityCheckFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<ProductionTask, $this>
     */
    public function productionTask(): BelongsTo
    {
        return $this->belongsTo(ProductionTask::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function inspector(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'checked_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'result' => QualityCheckResult::class,
            'checked_at' => 'datetime',
        ];
    }
}
