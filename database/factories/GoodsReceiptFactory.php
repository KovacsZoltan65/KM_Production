<?php

namespace Database\Factories;

use App\Enums\GoodsReceiptStatus;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoodsReceipt>
 */
class GoodsReceiptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'receipt_number' => strtoupper(fake()->unique()->bothify('GR-2026-######')),
            'purchase_order_id' => PurchaseOrder::factory(),
            'status' => GoodsReceiptStatus::Draft,
            'received_by' => null,
            'received_at' => now(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
