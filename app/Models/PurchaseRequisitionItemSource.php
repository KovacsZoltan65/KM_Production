<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'purchase_requisition_item_id',
    'material_requirement_id',
    'quantity',
])]
class PurchaseRequisitionItemSource extends Model
{
    public function purchaseRequisitionItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisitionItem::class);
    }

    public function materialRequirement(): BelongsTo
    {
        return $this->belongsTo(MaterialRequirement::class);
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
        ];
    }
}
