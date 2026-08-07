<?php

namespace Database\Factories;

use App\Enums\SupplyProposalStatus;
use App\Enums\SupplyStrategy;
use App\Models\Item;
use App\Models\SupplyProposal;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SupplyProposal> */
class SupplyProposalFactory extends Factory
{
    protected $model = SupplyProposal::class;

    public function definition(): array
    {
        return [
            'strategy' => SupplyStrategy::Purchase,
            'item_id' => Item::factory(),
            'supplier_id' => null,
            'proposed_quantity' => fake()->randomFloat(3, 1, 1000),
            'unit' => 'db',
            'required_at' => now()->addWeek()->toDateString(),
            'proposed_supply_at' => now()->addDays(5)->toDateString(),
            'status' => SupplyProposalStatus::Draft,
            'reason_code' => 'manual_planning',
            'notes' => null,
            'created_by' => null,
        ];
    }

    public function proposed(): static
    {
        return $this->state(fn (): array => ['status' => SupplyProposalStatus::Proposed]);
    }

    public function approved(): static
    {
        return $this->state(fn (): array => ['status' => SupplyProposalStatus::Approved]);
    }
}
