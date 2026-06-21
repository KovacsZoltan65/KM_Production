<?php

namespace App\Models;

use App\Enums\StockReservationStatus;
use Database\Factories\StockReservationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'item_id',
    'location_id',
    'item_batch_id',
    'customer_order_item_id',
    'production_order_id',
    'reserved_quantity',
    'status',
    'reserved_by',
    'reserved_at',
    'released_at',
    'notes',
])]
class StockReservation extends Model
{
    /** @use HasFactory<StockReservationFactory> */
    use HasFactory, SoftDeletes;

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
     * @return BelongsTo<CustomerOrderItem, $this>
     */
    public function customerOrderItem(): BelongsTo
    {
        return $this->belongsTo(CustomerOrderItem::class);
    }

    /**
     * @return BelongsTo<ProductionOrder, $this>
     */
    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reserver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reserved_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reserved_quantity' => 'decimal:3',
            'status' => StockReservationStatus::class,
            'reserved_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }
}
