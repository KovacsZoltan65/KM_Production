<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable([
    'purchase_requisition_item_id',
    'material_requirement_id',
    'quantity',
])]
/**
 * A beszerzési igénytétel és az azt létrehozó anyagszükséglet kapcsolata.
 *
 * @property int $id
 * @property int $purchase_requisition_item_id
 * @property int $material_requirement_id
 * @property numeric-string $quantity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PurchaseRequisitionItem $purchaseRequisitionItem
 * @property-read MaterialRequirement $materialRequirement
 */
class PurchaseRequisitionItemSource extends Model
{
    /**
     * @return BelongsTo<PurchaseRequisitionItem, $this>
     */
    public function purchaseRequisitionItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisitionItem::class);
    }

    /**
     * @return BelongsTo<MaterialRequirement, $this>
     */
    public function materialRequirement(): BelongsTo
    {
        return $this->belongsTo(MaterialRequirement::class);
    }

    /**
     * @return array{quantity: 'decimal:3'}
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
        ];
    }
}
