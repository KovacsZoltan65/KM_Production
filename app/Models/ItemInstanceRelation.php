<?php

namespace App\Models;

use Database\Factories\ItemInstanceRelationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'parent_item_instance_id',
    'child_item_instance_id',
    'quantity',
])]
class ItemInstanceRelation extends Model
{
    /** @use HasFactory<ItemInstanceRelationFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<ItemInstance, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ItemInstance::class, 'parent_item_instance_id');
    }

    /**
     * @return BelongsTo<ItemInstance, $this>
     */
    public function child(): BelongsTo
    {
        return $this->belongsTo(ItemInstance::class, 'child_item_instance_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
        ];
    }
}
