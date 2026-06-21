<?php

namespace App\Models;

use Database\Factories\FactoryUnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'code',
    'name',
    'description',
    'daily_capacity_minutes',
    'shift_count',
    'is_active',
])]
class FactoryUnit extends Model
{
    /** @use HasFactory<FactoryUnitFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return HasMany<Location, $this>
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'daily_capacity_minutes' => 'integer',
            'shift_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
