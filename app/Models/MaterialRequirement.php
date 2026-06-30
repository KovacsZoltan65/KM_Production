<?php

namespace App\Models;

use App\Enums\MaterialRequirementStatus;
use Database\Factories\MaterialRequirementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $customer_order_item_id
 * @property int $required_item_id
 * @property numeric $required_quantity
 * @property numeric $available_quantity
 * @property numeric $reserved_quantity
 * @property numeric $missing_quantity
 * @property string $unit
 * @property MaterialRequirementStatus $status
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read CustomerOrderItem|null $customerOrderItem
 * @property-read Item|null $requiredItem
 *
 * @method static \Database\Factories\MaterialRequirementFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequirement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequirement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequirement onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequirement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequirement whereAvailableQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequirement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequirement whereCustomerOrderItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequirement whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequirement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequirement whereMissingQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequirement whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequirement whereRequiredItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequirement whereRequiredQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequirement whereReservedQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequirement whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequirement whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequirement whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequirement withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequirement withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'customer_order_item_id',
    'required_item_id',
    'required_quantity',
    'available_quantity',
    'reserved_quantity',
    'missing_quantity',
    'unit',
    'status',
    'notes',
])]
class MaterialRequirement extends Model
{
    /** @use HasFactory<MaterialRequirementFactory> */
    use HasFactory, SoftDeletes;

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
    public function requiredItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'required_item_id');
    }

    /**
     * @return HasMany<PurchaseRequisitionItemSource, $this>
     */
    public function purchaseRequisitionSources(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionItemSource::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'required_quantity' => 'decimal:3',
            'available_quantity' => 'decimal:3',
            'reserved_quantity' => 'decimal:3',
            'missing_quantity' => 'decimal:3',
            'status' => MaterialRequirementStatus::class,
        ];
    }
}
