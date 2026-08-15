<?php

namespace Database\Factories;

use App\Models\PurchaseRequisitionItem;
use App\Models\PurchaseRequisitionItemProposalSource;
use App\Models\SupplyProposal;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PurchaseRequisitionItemProposalSource> */
class PurchaseRequisitionItemProposalSourceFactory extends Factory
{
    protected $model = PurchaseRequisitionItemProposalSource::class;

    public function definition(): array
    {
        return [
            'purchase_requisition_item_id' => PurchaseRequisitionItem::factory(),
            'supply_proposal_id' => SupplyProposal::factory()->approved(),
            'quantity' => fake()->randomFloat(3, 1, 1000),
        ];
    }
}
