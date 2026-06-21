<?php

namespace App\Models;

use App\Enums\PurchaseRequisitionItemStatus;
use Database\Factories\PurchaseRequisitionItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'purchase_requisition_id',
    'material_requirement_id',
    'item_id',
    'quantity',
    'unit',
    'status',
    'notes',
])]
class PurchaseRequisitionItem extends Model
{
    /** @use HasFactory<PurchaseRequisitionItemFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<PurchaseRequisition, $this>
     */
    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    /**
     * @return BelongsTo<MaterialRequirement, $this>
     */
    public function materialRequirement(): BelongsTo
    {
        return $this->belongsTo(MaterialRequirement::class);
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'status' => PurchaseRequisitionItemStatus::class,
        ];
    }
}
