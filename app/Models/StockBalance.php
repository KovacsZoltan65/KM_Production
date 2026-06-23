<?php

namespace App\Models;

use Database\Factories\StockBalanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $item_id
 * @property int $location_id
 * @property int|null $item_batch_id
 * @property numeric $quantity
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Item|null $item
 * @property-read \App\Models\ItemBatch|null $itemBatch
 * @property-read \App\Models\Location|null $location
 * @method static \Database\Factories\StockBalanceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockBalance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockBalance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockBalance query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockBalance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockBalance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockBalance whereItemBatchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockBalance whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockBalance whereLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockBalance whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockBalance whereUpdatedAt($value)
 * @mixin \Eloquent
 */
#[Fillable([
    'item_id',
    'location_id',
    'item_batch_id',
    'quantity',
])]
class StockBalance extends Model
{
    /** @use HasFactory<StockBalanceFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return BelongsTo<ItemBatch, $this>
     */
    public function itemBatch(): BelongsTo
    {
        return $this->belongsTo(ItemBatch::class);
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
