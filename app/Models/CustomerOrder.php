<?php

namespace App\Models;

use App\Enums\CustomerOrderStatus;
use Database\Factories\CustomerOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $order_number
 * @property int $customer_id
 * @property CustomerOrderStatus $status
 * @property \Illuminate\Support\Carbon|null $requested_delivery_date
 * @property \Illuminate\Support\Carbon|null $confirmed_at
 * @property string|null $notes
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\Customer|null $customer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CustomerOrderItem> $items
 * @property-read int|null $items_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductionPlan> $productionPlans
 * @property-read int|null $production_plans_count
 * @method static \Database\Factories\CustomerOrderFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerOrder onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerOrder whereConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerOrder whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerOrder whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerOrder whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerOrder whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerOrder whereOrderNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerOrder whereRequestedDeliveryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerOrder whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerOrder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerOrder withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerOrder withoutTrashed()
 * @mixin \Eloquent
 */
#[Fillable([
    'order_number',
    'customer_id',
    'status',
    'requested_delivery_date',
    'confirmed_at',
    'notes',
    'created_by',
])]
class CustomerOrder extends Model
{
    /** @use HasFactory<CustomerOrderFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<CustomerOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CustomerOrderItem::class);
    }

    /**
     * @return HasMany<ProductionPlan, $this>
     */
    public function productionPlans(): HasMany
    {
        return $this->hasMany(ProductionPlan::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CustomerOrderStatus::class,
            'requested_delivery_date' => 'date',
            'confirmed_at' => 'datetime',
        ];
    }
}
