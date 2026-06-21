<?php

namespace App\Models;

use Database\Factories\OperationSequenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        return $this->hasMany(OperationSequenceStep::class);
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
