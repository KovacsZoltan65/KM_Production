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
