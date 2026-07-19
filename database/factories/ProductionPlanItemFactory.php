<?php

namespace Database\Factories;

use App\Enums\ProductionPlanItemStatus;
use App\Models\Bom;
use App\Models\CustomerOrderItem;
use App\Models\Item;
use App\Models\OperationSequence;
use App\Models\ProductionPlan;
use App\Models\ProductionPlanItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionPlanItem>
 */
class ProductionPlanItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'production_plan_id' => ProductionPlan::factory(),
            'customer_order_item_id' => CustomerOrderItem::factory(),
            'item_id' => Item::factory()->finishedProduct(),
            'bom_id' => Bom::factory(),
            'operation_sequence_id' => OperationSequence::factory(),
            'quantity' => fake()->randomFloat(3, 1, 10),
            'planned_start_date' => fake()->optional()->passthrough(
                fake()->dateTimeBetween('+1 day', '+2 weeks')->format('Y-m-d')
            ),
            'planned_finish_date' => fake()->optional()->passthrough(
                fake()->dateTimeBetween('+2 weeks', '+3 months')->format('Y-m-d')
            ),
            'status' => ProductionPlanItemStatus::Draft,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
