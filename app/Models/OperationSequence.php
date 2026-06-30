<?php

namespace App\Models;

use Database\Factories\OperationSequenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $item_id
 * @property int $version
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Item|null $item
 * @property-read Collection<int, OperationSequenceStep> $steps
 * @property-read int|null $steps_count
 *
 * @method static \Database\Factories\OperationSequenceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationSequence newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationSequence newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationSequence onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationSequence query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationSequence whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationSequence whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationSequence whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationSequence whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationSequence whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationSequence whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationSequence whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationSequence whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationSequence whereVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationSequence withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationSequence withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'item_id',
    'version',
    'name',
    'description',
    'is_active',
])]
class OperationSequence extends Model
{
    /** @use HasFactory<OperationSequenceFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * @return HasMany<OperationSequenceStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(OperationSequenceStep::class)->orderBy('step_order');
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
