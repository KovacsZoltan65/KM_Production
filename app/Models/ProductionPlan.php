<?php

namespace App\Models;

use App\Enums\ProductionPlanStatus;
use Database\Factories\ProductionPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'customer_order_id',
    'plan_number',
    'status',
    'planned_start_date',
    'planned_finish_date',
    'created_by',
    'approved_by',
    'approved_at',
    'notes',
])]
class ProductionPlan extends Model
{
    /** @use HasFactory<ProductionPlanFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<CustomerOrder, $this>
     */
    public function customerOrder(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return HasMany<ProductionPlanItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ProductionPlanItem::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProductionPlanStatus::class,
            'planned_start_date' => 'date',
            'planned_finish_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }
}
