<?php

namespace App\Models;

use App\Enums\ProductionPlanStatus;
use Database\Factories\ProductionPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $customer_order_id
 * @property string $plan_number
 * @property ProductionPlanStatus $status
 * @property Carbon|null $planned_start_date
 * @property Carbon|null $planned_finish_date
 * @property int|null $created_by
 * @property int|null $approved_by
 * @property Carbon|null $approved_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User|null $approver
 * @property-read User|null $creator
 * @property-read CustomerOrder|null $customerOrder
 * @property-read Collection<int, ProductionPlanItem> $items
 * @property-read int|null $items_count
 *
 * @method static \Database\Factories\ProductionPlanFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlan onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlan whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlan whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlan whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlan whereCustomerOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlan whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlan whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlan wherePlanNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlan wherePlannedFinishDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlan wherePlannedStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlan whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlan whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlan withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionPlan withoutTrashed()
 *
 * @mixin \Eloquent
 */
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
