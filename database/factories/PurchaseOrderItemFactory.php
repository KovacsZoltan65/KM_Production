<?php

namespace Database\Factories;

use App\Enums\PurchaseOrderItemStatus;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrderItem>
 */
class PurchaseOrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'purchase_requisition_item_id' => null,
            'item_id' => Item::factory()->purchasedMaterial(),
            'ordered_quantity' => fake()->randomFloat(3, 1, 100),
            'received_quantity' => 0,
            'unit' => 'db',
            'status' => PurchaseOrderItemStatus::Ordered,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
