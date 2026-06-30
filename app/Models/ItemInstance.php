<?php

namespace App\Models;

use App\Enums\ItemInstanceStatus;
use Database\Factories\ItemInstanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $item_id
 * @property string $serial_number
 * @property int $factory_unit_id
 * @property int|null $current_location_id
 * @property ItemInstanceStatus $current_status
 * @property int|null $production_order_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, ItemInstanceRelation> $childRelations
 * @property-read int|null $child_relations_count
 * @property-read Location|null $currentLocation
 * @property-read FactoryUnit|null $factoryUnit
 * @property-read Item|null $item
 * @property-read Collection<int, ItemInstanceRelation> $parentRelations
 * @property-read int|null $parent_relations_count
 *
 * @method static \Database\Factories\ItemInstanceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemInstance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemInstance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemInstance query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemInstance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemInstance whereCurrentLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemInstance whereCurrentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemInstance whereFactoryUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemInstance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemInstance whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemInstance whereProductionOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemInstance whereSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemInstance whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'item_id',
    'serial_number',
    'factory_unit_id',
    'current_location_id',
    'current_status',
    'production_order_id',
])]
class ItemInstance extends Model
{
    /** @use HasFactory<ItemInstanceFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * @return BelongsTo<FactoryUnit, $this>
     */
    public function factoryUnit(): BelongsTo
    {
        return $this->belongsTo(FactoryUnit::class);
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function currentLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'current_location_id');
    }

    /**
     * Relations where this instance is the parent assembly.
     *
     * @return HasMany<ItemInstanceRelation, $this>
     */
    public function childRelations(): HasMany
    {
        return $this->hasMany(ItemInstanceRelation::class, 'parent_item_instance_id');
    }

    /**
     * Relations where this instance is used as a child component.
     *
     * @return HasMany<ItemInstanceRelation, $this>
     */
    public function parentRelations(): HasMany
    {
        return $this->hasMany(ItemInstanceRelation::class, 'child_item_instance_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'current_status' => ItemInstanceStatus::class,
        ];
    }
}
