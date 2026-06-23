<?php

namespace App\Models;

use App\Enums\PurchaseOrderItemStatus;
use Database\Factories\PurchaseOrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $purchase_order_id
 * @property int|null $purchase_requisition_item_id
 * @property int $item_id
 * @property numeric $ordered_quantity
 * @property numeric $received_quantity
 * @property string $unit
 * @property PurchaseOrderItemStatus $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Item|null $item
 * @property-read \App\Models\PurchaseOrder|null $purchaseOrder
 * @property-read \App\Models\PurchaseRequisitionItem|null $purchaseRequisitionItem
 * @method static \Database\Factories\PurchaseOrderItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem whereOrderedQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem wherePurchaseOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem wherePurchaseRequisitionItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem whereReceivedQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem withoutTrashed()
 * @mixin \Eloquent
 */
#[Fillable([
    'purchase_order_id',
    'purchase_requisition_item_id',
    'item_id',
    'ordered_quantity',
    'received_quantity',
    'unit',
    'status',
    'notes',
])]
class PurchaseOrderItem extends Model
{
    /** @use HasFactory<PurchaseOrderItemFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<PurchaseOrder, $this>
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * @return BelongsTo<PurchaseRequisitionItem, $this>
     */
    public function purchaseRequisitionItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisitionItem::class);
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
            'ordered_quantity' => 'decimal:3',
            'received_quantity' => 'decimal:3',
            'status' => PurchaseOrderItemStatus::class,
        ];
    }
}
