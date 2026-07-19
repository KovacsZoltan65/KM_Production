<?php

namespace Database\Factories;

use App\Enums\ProductionOrderStatus;
use App\Models\Bom;
use App\Models\CustomerOrderItem;
use App\Models\Item;
use App\Models\OperationSequence;
use App\Models\ProductionOrder;
use App\Models\ProductionPlanItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionOrder>
 */
class ProductionOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'production_plan_item_id' => ProductionPlanItem::factory(),
            'customer_order_item_id' => CustomerOrderItem::factory(),
            'item_id' => Item::factory()->finishedProduct(),
            'bom_id' => Bom::factory(),
            'operation_sequence_id' => OperationSequence::factory(),
            'order_number' => strtoupper(fake()->unique()->bothify('PO-2026-######')),
            'quantity' => fake()->randomFloat(3, 1, 10),
            'status' => ProductionOrderStatus::Planned,
            'planned_start_date' => fake()->optional()->passthrough(
                fake()->dateTimeBetween('+1 day', '+2 weeks')->format('Y-m-d')
            ),
            'planned_finish_date' => fake()->optional()->passthrough(
                fake()->dateTimeBetween('+2 weeks', '+3 months')->format('Y-m-d')
            ),
            'started_at' => null,
            'finished_at' => null,
            'created_by' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
