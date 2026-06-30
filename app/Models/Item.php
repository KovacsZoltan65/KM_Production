<?php

namespace App\Models;

use App\Enums\ItemType;
use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $item_number
 * @property string $name
 * @property ItemType $item_type
 * @property string $unit
 * @property numeric|null $width
 * @property numeric|null $length
 * @property numeric|null $thickness
 * @property numeric|null $diameter
 * @property bool $requires_serial_number
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, ItemBatch> $batches
 * @property-read int|null $batches_count
 * @property-read Collection<int, CustomerOrderItem> $customerOrderItems
 * @property-read int|null $customer_order_items_count
 * @property-read Collection<int, ItemInstance> $instances
 * @property-read int|null $instances_count
 * @property-read Collection<int, ProductionOrder> $productionOrders
 * @property-read int|null $production_orders_count
 *
 * @method static \Database\Factories\ItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereDiameter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereItemNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereItemType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereLength($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereRequiresSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereThickness($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereWidth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'item_number',
    'name',
    'item_type',
    'unit',
    'width',
    'length',
    'thickness',
    'diameter',
    'requires_serial_number',
    'is_active',
])]
class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return HasMany<ItemBatch, $this>
     */
    public function batches(): HasMany
    {
        return $this->hasMany(ItemBatch::class);
    }

    /**
     * @return HasMany<ItemInstance, $this>
     */
    public function instances(): HasMany
    {
        return $this->hasMany(ItemInstance::class);
    }

    /**
     * @return HasMany<CustomerOrderItem, $this>
     */
    public function customerOrderItems(): HasMany
    {
        return $this->hasMany(CustomerOrderItem::class);
    }

    /**
     * @return HasMany<ProductionOrder, $this>
     */
    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class);
    }

    public function requiresSerialNumberByType(): bool
    {
        return $this->item_type instanceof ItemType
            ? $this->item_type->requiresSerialNumber()
            : ItemType::from((string) $this->item_type)->requiresSerialNumber();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'item_type' => ItemType::class,
            'width' => 'decimal:3',
            'length' => 'decimal:3',
            'thickness' => 'decimal:3',
            'diameter' => 'decimal:3',
            'requires_serial_number' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
