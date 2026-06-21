<?php

namespace App\Models;

use App\Enums\ItemType;
use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
