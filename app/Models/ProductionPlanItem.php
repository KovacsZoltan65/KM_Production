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

/**
 * @property int $id
 * @property int $production_plan_id
 * @property int $customer_order_item_id
 * @property int $item_id
 * @property int|null $bom_id
 * @property int|null $operation_sequence_id
 * @property numeric $quantity
 * @property \Illuminate\Support\Carbon|null $planned_start_date
 * @property \Illuminate\Support\Carbon|null $planned_finish_date
 * @property ProductionPlanItemStatus $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\CustomerOrderItem|null $customerOrderItem
 * @property-read \App\Models\Bom|null $bom
 * @property-read \App\Models\Item|null $item
 * @property-read \App\Models\OperationSequence|null $operationSequence
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductionOrder> $productionOrders
 * @property-read int|null $production_orders_count
 * @property-read \App\Models\ProductionPlan|null $productionPlan
 * @method static \Database\Factories\ProductionPlanItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlanItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlanItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlanItem onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlanItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlanItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlanItem whereCustomerOrderItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlanItem whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlanItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlanItem whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlanItem whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlanItem wherePlannedFinishDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlanItem wherePlannedStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlanItem whereProductionPlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlanItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlanItem whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlanItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlanItem withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlanItem withoutTrashed()
 * @mixin \Eloquent
 */
#[Fillable([
    'production_plan_id',
    'customer_order_item_id',
    'item_id',
    'bom_id',
    'operation_sequence_id',
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
     * @return BelongsTo<Bom, $this>
     */
    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class);
    }

    /**
     * @return BelongsTo<OperationSequence, $this>
     */
    public function operationSequence(): BelongsTo
    {
        return $this->belongsTo(OperationSequence::class);
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
