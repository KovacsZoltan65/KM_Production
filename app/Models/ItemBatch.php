<?php

namespace App\Models;

use Database\Factories\ItemBatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $item_id
 * @property string $batch_number
 * @property int|null $supplier_id
 * @property \Illuminate\Support\Carbon|null $received_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Item|null $item
 * @property-read \App\Models\Supplier|null $supplier
 * @method static \Database\Factories\ItemBatchFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemBatch newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemBatch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemBatch onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemBatch query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemBatch whereBatchNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemBatch whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemBatch whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemBatch whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemBatch whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemBatch whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemBatch whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemBatch whereReceivedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemBatch whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemBatch whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemBatch withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemBatch withoutTrashed()
 * @mixin \Eloquent
 */
#[Fillable([
    'item_id',
    'batch_number',
    'supplier_id',
    'received_at',
    'expires_at',
    'notes',
])]
class ItemBatch extends Model
{
    /** @use HasFactory<ItemBatchFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'received_at' => 'date',
            'expires_at' => 'date',
        ];
    }
}
