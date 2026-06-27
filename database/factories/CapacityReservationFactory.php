<?php

namespace Database\Factories;

use App\Models\CapacityReservation;
use App\Models\Employee;
use App\Models\FactoryUnit;
use App\Models\ProductionTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CapacityReservation>
 */
class CapacityReservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $from = now()->addDay()->setTime(8, 0);

        return [
            'production_task_id' => ProductionTask::factory(),
            'factory_unit_id' => FactoryUnit::factory(),
            'employee_id' => Employee::factory(),
            'reserved_from' => $from,
            'reserved_until' => $from->copy()->addMinutes(60),
            'planned_minutes' => 60,
            'status' => 'planned',
        ];
    }
}
