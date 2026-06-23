<?php

namespace App\Models;

use App\Enums\CustomerOrderItemStatus;
use Database\Factories\CustomerOrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $customer_order_id
 * @property int $item_id
 * @property numeric $quantity
 * @property string $unit
 * @property CustomerOrderItemStatus $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\CustomerOrder|null $customerOrder
 * @property-read \App\Models\Item|null $item
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductionOrder> $productionOrders
 * @property-read int|null $production_orders_count
 * @method static \Database\Factories\CustomerOrderItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerOrderItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerOrderItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerOrderItem onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerOrderItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerOrderItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerOrderItem whereCustomerOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerOrderItem whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerOrderItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerOrderItem whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerOrderItem whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerOrderItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerOrderItem whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerOrderItem whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerOrderItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerOrderItem withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerOrderItem withoutTrashed()
 * @mixin \Eloquent
 */
#[Fillable([
    'customer_order_id',
    'item_id',
    'quantity',
    'unit',
    'status',
    'notes',
])]
class CustomerOrderItem extends Model
{
    /** @use HasFactory<CustomerOrderItemFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<CustomerOrder, $this>
     */
    public function customerOrder(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class);
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * @return HasMany<ProductionOrder, $this>
     */
    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'status' => CustomerOrderItemStatus::class,
        ];
    }
}
