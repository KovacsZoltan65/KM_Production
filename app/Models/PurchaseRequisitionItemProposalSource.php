<?php

namespace App\Models;

use Database\Factories\PurchaseRequisitionItemProposalSourceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Explicit execution lineage from an approved Supply Proposal to one PR item. */
#[Fillable([
    'purchase_requisition_item_id',
    'supply_proposal_id',
    'quantity',
])]
class PurchaseRequisitionItemProposalSource extends Model
{
    /** @use HasFactory<PurchaseRequisitionItemProposalSourceFactory> */
    use HasFactory;

    /** @return BelongsTo<PurchaseRequisitionItem, $this> */
    public function purchaseRequisitionItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisitionItem::class);
    }

    /** @return BelongsTo<SupplyProposal, $this> */
    public function supplyProposal(): BelongsTo
    {
        return $this->belongsTo(SupplyProposal::class);
    }

    /** @return array{quantity: 'decimal:3'} */
    protected function casts(): array
    {
        return ['quantity' => 'decimal:3'];
    }
}
