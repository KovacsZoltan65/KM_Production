<?php

namespace App\Models;

use App\Enums\OperationTypeCode;
use Database\Factories\OperationTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
