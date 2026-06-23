<?php

namespace App\Models;

use Database\Factories\ItemInstanceRelationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $parent_item_instance_id
 * @property int $child_item_instance_id
 * @property numeric $quantity
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ItemInstance $child
 * @property-read \App\Models\ItemInstance $parent
 * @method static \Database\Factories\ItemInstanceRelationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemInstanceRelation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemInstanceRelation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemInstanceRelation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemInstanceRelation whereChildItemInstanceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemInstanceRelation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemInstanceRelation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemInstanceRelation whereParentItemInstanceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemInstanceRelation whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemInstanceRelation whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
