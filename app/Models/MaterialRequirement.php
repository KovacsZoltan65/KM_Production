<?php

namespace App\Models;

use App\Enums\MaterialRequirementStatus;
use Database\Factories\MaterialRequirementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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
