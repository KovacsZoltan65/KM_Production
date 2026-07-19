<?php

namespace Database\Factories;

use App\Enums\ProductionPlanStatus;
use App\Models\CustomerOrder;
use App\Models\ProductionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionPlan>
 */
class ProductionPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_order_id' => CustomerOrder::factory(),
            'plan_number' => strtoupper(fake()->unique()->bothify('PP-2026-######')),
            'status' => ProductionPlanStatus::Draft,
            'planned_start_date' => fake()->optional()->passthrough(
                fake()->dateTimeBetween('+1 day', '+2 weeks')->format('Y-m-d')
            ),
            'planned_finish_date' => fake()->optional()->passthrough(
                fake()->dateTimeBetween('+2 weeks', '+3 months')->format('Y-m-d')
            ),
            'created_by' => null,
            'approved_by' => null,
            'approved_at' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
