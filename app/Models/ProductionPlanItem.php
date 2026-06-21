<?php

namespace App\Models;

use App\Enums\ProductionPlanItemStatus;
use Database\Factories\ProductionPlanItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'production_plan_id',
    'customer_order_item_id',
    'item_id',
    'quantity',
    'planned_start_date',
    'planned_finish_date',
    'status',
    'notes',
])]
class ProductionPlanItem extends Model
{
    /** @use HasFactory<ProductionPlanItemFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<ProductionPlan, $this>
     */
    public function productionPlan(): BelongsTo
    {
        return $this->belongsTo(ProductionPlan::class);
    }

    /**
     * @return BelongsTo<CustomerOrderItem, $this>
     */
    public function customerOrderItem(): BelongsTo
    {
        return $this->belongsTo(CustomerOrderItem::class);
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
            'planned_start_date' => 'date',
            'planned_finish_date' => 'date',
            'status' => ProductionPlanItemStatus::class,
        ];
    }
}
