<?php

namespace App\Models;

use Database\Factories\CapacityReservationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable([
    'production_task_id',
    'factory_unit_id',
    'employee_id',
    'reserved_from',
    'reserved_until',
    'planned_minutes',
    'status',
])]
/**
 * A gyártási feladathoz rendelt gyáregységi kapacitásfoglalás.
 *
 * @property int $id
 * @property int $production_task_id
 * @property int $factory_unit_id
 * @property int|null $employee_id
 * @property Carbon $reserved_from
 * @property Carbon $reserved_until
 * @property int $planned_minutes
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ProductionTask $productionTask
 * @property-read FactoryUnit $factoryUnit
 * @property-read Employee|null $employee
 */
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
     * @return array{reserved_from: 'datetime', reserved_until: 'datetime',
     *     planned_minutes: 'integer'}
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
