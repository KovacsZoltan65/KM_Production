<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\ProductionTask;
use App\Models\ProductionTaskMaterial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionTaskMaterial>
 */
class ProductionTaskMaterialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plannedQuantity = fake()->randomFloat(3, 1, 20);

        return [
            'production_task_id' => ProductionTask::factory(),
            'item_id' => Item::factory()->purchasedMaterial(),
            'item_batch_id' => null,
            'planned_quantity' => $plannedQuantity,
            'used_quantity' => $plannedQuantity,
            'unit' => 'db',
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
