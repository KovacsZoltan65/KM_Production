<?php

namespace Database\Factories;

use App\Enums\PurchaseRequisitionItemStatus;
use App\Models\Item;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseRequisitionItem>
 */
class PurchaseRequisitionItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_requisition_id' => PurchaseRequisition::factory(),
            'material_requirement_id' => null,
            'item_id' => Item::factory()->purchasedMaterial(),
            'quantity' => fake()->randomFloat(3, 1, 100),
            'unit' => 'db',
            'status' => PurchaseRequisitionItemStatus::Draft,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
