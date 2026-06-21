<?php

namespace Database\Factories;

use App\Enums\ProductionTaskStatus;
use App\Models\Employee;
use App\Models\ItemInstance;
use App\Models\OperationSequenceStep;
use App\Models\ProductionOrder;
use App\Models\ProductionTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionTask>
 */
class ProductionTaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'production_order_id' => ProductionOrder::factory(),
            'item_instance_id' => ItemInstance::factory(),
            'operation_sequence_step_id' => OperationSequenceStep::factory(),
            'employee_id' => Employee::factory(),
            'status' => ProductionTaskStatus::Planned,
            'started_at' => null,
            'finished_at' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
