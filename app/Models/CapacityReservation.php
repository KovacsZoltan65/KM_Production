<?php

namespace App\Models;

use Database\Factories\CapacityReservationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'production_task_id',
    'factory_unit_id',
    'employee_id',
    'reserved_from',
    'reserved_until',
    'planned_minutes',
    'status',
])]
class CapacityReservation extends Model
{
    /** @use HasFactory<CapacityReservationFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<ProductionTask, $this>
     */
    public function productionTask(): BelongsTo
    {
        return $this->belongsTo(ProductionTask::class);
    }

    /**
     * @return BelongsTo<FactoryUnit, $this>
     */
    public function factoryUnit(): BelongsTo
    {
        return $this->belongsTo(FactoryUnit::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reserved_from' => 'datetime',
            'reserved_until' => 'datetime',
            'planned_minutes' => 'integer',
        ];
    }
}
