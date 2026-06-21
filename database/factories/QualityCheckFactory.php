<?php

namespace Database\Factories;

use App\Enums\QualityCheckResult;
use App\Models\Employee;
use App\Models\ProductionTask;
use App\Models\QualityCheck;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QualityCheck>
 */
class QualityCheckFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'production_task_id' => ProductionTask::factory(),
            'checked_by' => Employee::factory(),
            'result' => QualityCheckResult::Accepted,
            'checked_at' => now(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
