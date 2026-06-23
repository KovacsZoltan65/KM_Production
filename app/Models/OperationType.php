<?php

namespace App\Models;

use App\Enums\OperationTypeCode;
use Database\Factories\OperationTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property OperationTypeCode $code
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OperationSequenceStep> $operationSequenceSteps
 * @property-read int|null $operation_sequence_steps_count
 * @method static \Database\Factories\OperationTypeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationType onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationType whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationType whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationType whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationType whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationType whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationType withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationType withoutTrashed()
 * @mixin \Eloquent
 */
#[Fillable([
    'code',
    'name',
    'description',
    'is_active',
])]
class OperationType extends Model
{
    /** @use HasFactory<OperationTypeFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return HasMany<OperationSequenceStep, $this>
     */
    public function operationSequenceSteps(): HasMany
    {
        return $this->hasMany(OperationSequenceStep::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'code' => OperationTypeCode::class,
            'is_active' => 'boolean',
        ];
    }
}
