<?php

namespace App\Models;

use App\Enums\ProductionTaskStatus;
use Database\Factories\ProductionTaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'production_order_id',
    'item_instance_id',
    'operation_sequence_step_id',
    'employee_id',
    'status',
    'started_at',
    'finished_at',
    'notes',
])]
class ProductionTask extends Model
{
    /** @use HasFactory<ProductionTaskFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<ProductionOrder, $this>
     */
    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    /**
     * @return BelongsTo<ItemInstance, $this>
     */
    public function itemInstance(): BelongsTo
    {
        return $this->belongsTo(ItemInstance::class);
    }

    /**
     * @return BelongsTo<OperationSequenceStep, $this>
     */
    public function operationSequenceStep(): BelongsTo
    {
        return $this->belongsTo(OperationSequenceStep::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return HasMany<ProductionTaskMaterial, $this>
     */
    public function materials(): HasMany
    {
        return $this->hasMany(ProductionTaskMaterial::class);
    }

    /**
     * @return HasMany<QualityCheck, $this>
     */
    public function qualityChecks(): HasMany
    {
        return $this->hasMany(QualityCheck::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProductionTaskStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
