<?php

namespace App\Models;

use App\Enums\StockReservationStatus;
use Database\Factories\StockReservationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $item_id
 * @property int|null $location_id
 * @property int|null $item_batch_id
 * @property int|null $customer_order_item_id
 * @property int|null $production_order_id
 * @property numeric $reserved_quantity
 * @property StockReservationStatus $status
 * @property int|null $reserved_by
 * @property \Illuminate\Support\Carbon|null $reserved_at
 * @property \Illuminate\Support\Carbon|null $released_at
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\CustomerOrderItem|null $customerOrderItem
 * @property-read \App\Models\Item|null $item
 * @property-read \App\Models\ItemBatch|null $itemBatch
 * @property-read \App\Models\Location|null $location
 * @property-read \App\Models\ProductionOrder|null $productionOrder
 * @property-read \App\Models\User|null $reserver
 * @method static \Database\Factories\StockReservationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockReservation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockReservation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockReservation onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockReservation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockReservation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockReservation whereCustomerOrderItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockReservation whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockReservation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockReservation whereItemBatchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockReservation whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockReservation whereLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockReservation whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockReservation whereProductionOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockReservation whereReleasedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockReservation whereReservedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockReservation whereReservedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockReservation whereReservedQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockReservation whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockReservation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockReservation withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockReservation withoutTrashed()
 * @mixin \Eloquent
 */
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
