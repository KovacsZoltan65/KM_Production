<?php

namespace App\Models;

use App\Enums\ProductionOrderStatus;
use Database\Factories\ProductionOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $production_plan_item_id
 * @property int $customer_order_item_id
 * @property int $item_id
 * @property int $bom_id
 * @property int $operation_sequence_id
 * @property string $order_number
 * @property numeric $quantity
 * @property ProductionOrderStatus $status
 * @property \Illuminate\Support\Carbon|null $planned_start_date
 * @property \Illuminate\Support\Carbon|null $planned_finish_date
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $finished_at
 * @property int|null $created_by
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Bom|null $bom
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\CustomerOrderItem|null $customerOrderItem
 * @property-read \App\Models\Item|null $item
 * @property-read \App\Models\OperationSequence|null $operationSequence
 * @property-read \App\Models\ProductionPlanItem|null $productionPlanItem
 * @method static \Database\Factories\ProductionOrderFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereBomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereCustomerOrderItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereFinishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereOperationSequenceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereOrderNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder wherePlannedFinishDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder wherePlannedStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereProductionPlanItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder withoutTrashed()
 * @mixin \Eloquent
 */
#[Fillable([
    'production_plan_item_id',
    'customer_order_item_id',
    'item_id',
    'bom_id',
    'operation_sequence_id',
    'order_number',
    'quantity',
    'status',
    'planned_start_date',
    'planned_finish_date',
    'started_at',
    'finished_at',
    'created_by',
    'notes',
])]
class ProductionOrder extends Model
{
    /** @use HasFactory<ProductionOrderFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<ProductionPlanItem, $this>
     */
    public function productionPlanItem(): BelongsTo
    {
        return $this->belongsTo(ProductionPlanItem::class);
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'status' => ProductionOrderStatus::class,
            'planned_start_date' => 'date',
            'planned_finish_date' => 'date',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
