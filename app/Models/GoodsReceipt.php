<?php

namespace App\Models;

use Database\Factories\GoodsReceiptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $receipt_number
 * @property int|null $purchase_order_id
 * @property int|null $received_by
 * @property \Illuminate\Support\Carbon $received_at
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GoodsReceiptItem> $items
 * @property-read int|null $items_count
 * @property-read \App\Models\PurchaseOrder|null $purchaseOrder
 * @property-read \App\Models\User|null $receiver
 * @method static \Database\Factories\GoodsReceiptFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt wherePurchaseOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt whereReceiptNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt whereReceivedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt whereReceivedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt withoutTrashed()
 * @mixin \Eloquent
 */
#[Fillable([
    'receipt_number',
    'purchase_order_id',
    'received_by',
    'received_at',
    'notes',
])]
class GoodsReceipt extends Model
{
    /** @use HasFactory<GoodsReceiptFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<PurchaseOrder, $this>
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * @return HasMany<GoodsReceiptItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
        ];
    }
}
