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
