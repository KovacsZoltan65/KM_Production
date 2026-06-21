<?php

namespace Database\Factories;

use App\Enums\CustomerOrderItemStatus;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderItem;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerOrderItem>
 */
class CustomerOrderItemFactory extends Factory
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
            'item_id' => Item::factory()->finishedProduct(),
            'quantity' => fake()->randomFloat(3, 1, 10),
            'unit' => 'db',
            'status' => CustomerOrderItemStatus::Draft,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
