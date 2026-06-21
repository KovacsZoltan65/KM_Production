<?php

namespace App\Models;

use App\Enums\ProductionOrderStatus;
use Database\Factories\ProductionOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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
