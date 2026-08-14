<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'material_requirement_id',
    'stock_balance_id',
    'purchase_order_item_id',
    'quantity',
    'unit',
    'supply_at',
    'calculated_at',
])]
class MaterialRequirementPeg extends Model
{
    public function materialRequirement(): BelongsTo
    {
        return $this->belongsTo(MaterialRequirement::class);
    }

    public function stockBalance(): BelongsTo
    {
        return $this->belongsTo(StockBalance::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'supply_at' => 'date',
            'calculated_at' => 'datetime',
        ];
    }
}
