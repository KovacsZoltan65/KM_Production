<?php

namespace App\Models;

use App\Enums\ProductionTaskStatus;
use Database\Factories\ProductionTaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $production_order_id
 * @property int $item_instance_id
 * @property int $operation_sequence_step_id
 * @property int $employee_id
 * @property ProductionTaskStatus $status
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Employee|null $employee
 * @property-read ItemInstance $itemInstance
 * @property-read Collection<int, ProductionTaskMaterial> $materials
 * @property-read int|null $materials_count
 * @property-read OperationSequenceStep $operationSequenceStep
 * @property-read ProductionOrder|null $productionOrder
 * @property-read Collection<int, QualityCheck> $qualityChecks
 * @property-read int|null $quality_checks_count
 *
 * @method static \Database\Factories\ProductionTaskFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionTask newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionTask newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionTask query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionTask whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionTask whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionTask whereFinishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionTask whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionTask whereItemInstanceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionTask whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionTask whereOperationSequenceStepId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionTask whereProductionOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionTask whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionTask whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionTask whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
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
     * @return HasMany<CapacityReservation, $this>
     */
    public function capacityReservations(): HasMany
    {
        return $this->hasMany(CapacityReservation::class);
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
