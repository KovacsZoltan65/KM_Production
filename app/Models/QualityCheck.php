<?php

namespace App\Models;

use App\Enums\QualityCheckResult;
use Database\Factories\QualityCheckFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
