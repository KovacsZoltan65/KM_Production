<?php

namespace App\Models;

use App\Enums\ItemInstanceStatus;
use Database\Factories\ItemInstanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
