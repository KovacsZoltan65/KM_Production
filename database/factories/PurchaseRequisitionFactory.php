<?php

namespace Database\Factories;

use App\Enums\PurchaseRequisitionStatus;
use App\Models\PurchaseRequisition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseRequisition>
 */
class PurchaseRequisitionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'requisition_number' => strtoupper(fake()->unique()->bothify('PR-2026-######')),
            'status' => PurchaseRequisitionStatus::Draft,
            'supplier_id' => null,
            'requested_by' => null,
            'requested_at' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'required_at' => fake()->optional()->dateTimeBetween('now', '+1 month'),
            'proposed_supply_at' => fake()->optional()->dateTimeBetween('now', '+1 month'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
