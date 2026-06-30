<?php

namespace App\Models;

use Database\Factories\BomItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $bom_id
 * @property int $item_id
 * @property numeric $quantity
 * @property string $unit
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Bom|null $bom
 * @property-read Item|null $item
 *
 * @method static \Database\Factories\BomItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BomItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BomItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BomItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BomItem whereBomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BomItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BomItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BomItem whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BomItem whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BomItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BomItem whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BomItem whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'bom_id',
    'item_id',
    'quantity',
    'unit',
    'notes',
])]
class BomItem extends Model
{
    /** @use HasFactory<BomItemFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Bom, $this>
     */
    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class);
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
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
