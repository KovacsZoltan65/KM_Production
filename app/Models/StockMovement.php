<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $item_id
 * @property int|null $item_batch_id
 * @property int|null $item_instance_id
 * @property int|null $from_location_id
 * @property int|null $to_location_id
 * @property numeric $quantity
 * @property StockMovementType $movement_type
 * @property string|null $source_type
 * @property int|null $source_id
 * @property int|null $performed_by
 * @property Carbon $performed_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Location|null $fromLocation
 * @property-read Item|null $item
 * @property-read ItemBatch|null $itemBatch
 * @property-read ItemInstance|null $itemInstance
 * @property-read User|null $performer
 * @property-read Model|null $source
 * @property-read Location|null $toLocation
 *
 * @method static \Database\Factories\StockMovementFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereFromLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereItemBatchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereItemInstanceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereMovementType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement wherePerformedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement wherePerformedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereSourceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereSourceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereToLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'item_id',
    'item_batch_id',
    'item_instance_id',
    'from_location_id',
    'to_location_id',
    'quantity',
    'movement_type',
    'source_type',
    'source_id',
    'performed_by',
    'performed_at',
    'notes',
])]
class StockMovement extends Model
{
    /** @use HasFactory<StockMovementFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * @return BelongsTo<ItemBatch, $this>
     */
    public function itemBatch(): BelongsTo
    {
        return $this->belongsTo(ItemBatch::class);
    }

    /**
     * @return BelongsTo<ItemInstance, $this>
     */
    public function itemInstance(): BelongsTo
    {
        return $this->belongsTo(ItemInstance::class);
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'movement_type' => StockMovementType::class,
            'performed_at' => 'datetime',
        ];
    }
}
