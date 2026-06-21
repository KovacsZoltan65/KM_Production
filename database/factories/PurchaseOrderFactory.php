<?php

namespace Database\Factories;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_number' => strtoupper(fake()->unique()->bothify('PO-SUP-2026-######')),
            'supplier_id' => Supplier::factory(),
            'purchase_requisition_id' => PurchaseRequisition::factory(),
            'status' => PurchaseOrderStatus::Draft,
            'ordered_at' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'expected_delivery_date' => fake()->optional()->dateTimeBetween('+1 week', '+2 months')?->format('Y-m-d'),
            'notes' => fake()->optional()->sentence(),
            'created_by' => null,
        ];
    }
}
