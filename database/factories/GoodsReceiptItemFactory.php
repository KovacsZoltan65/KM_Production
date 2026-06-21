<?php

namespace Database\Factories;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Item;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoodsReceiptItem>
 */
class GoodsReceiptItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'goods_receipt_id' => GoodsReceipt::factory(),
            'purchase_order_item_id' => null,
            'item_id' => Item::factory()->purchasedMaterial(),
            'item_batch_id' => null,
            'location_id' => Location::factory(),
            'quantity' => fake()->randomFloat(3, 1, 50),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
