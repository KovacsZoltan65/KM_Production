<?php

namespace App\Models;

use Database\Factories\BomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $item_id
 * @property int $version
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BomItem> $bomItems
 * @property-read int|null $bom_items_count
 * @property-read \App\Models\Item|null $item
 * @method static \Database\Factories\BomFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bom newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bom newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bom onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bom query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bom whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bom whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bom whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bom whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bom whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bom whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bom whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bom whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bom whereVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bom withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bom withoutTrashed()
 * @mixin \Eloquent
 */
#[Fillable([
    'item_id',
    'version',
    'name',
    'description',
    'is_active',
])]
class Bom extends Model
{
    /** @use HasFactory<BomFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * @return HasMany<BomItem, $this>
     */
    public function bomItems(): HasMany
    {
        return $this->hasMany(BomItem::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
