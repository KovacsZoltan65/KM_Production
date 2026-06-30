<?php

namespace App\Models;

use Database\Factories\GoodsReceiptItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $goods_receipt_id
 * @property int|null $purchase_order_item_id
 * @property int $item_id
 * @property int|null $item_batch_id
 * @property int $location_id
 * @property numeric $quantity
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read GoodsReceipt|null $goodsReceipt
 * @property-read Item|null $item
 * @property-read ItemBatch|null $itemBatch
 * @property-read Location|null $location
 * @property-read PurchaseOrderItem|null $purchaseOrderItem
 *
 * @method static \Database\Factories\GoodsReceiptItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem whereGoodsReceiptId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem whereItemBatchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem whereLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem wherePurchaseOrderItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'goods_receipt_id',
    'purchase_order_item_id',
    'item_id',
    'item_batch_id',
    'location_id',
    'quantity',
    'notes',
])]
class GoodsReceiptItem extends Model
{
    /** @use HasFactory<GoodsReceiptItemFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<GoodsReceipt, $this>
     */
    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    /**
     * @return BelongsTo<PurchaseOrderItem, $this>
     */
    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * @return BelongsTo<ItemBatch, $this>
     */
    public function itemBatch(): BelongsTo
    {
        return $this->belongsTo(ItemBatch::class);
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
        ];
    }
}
