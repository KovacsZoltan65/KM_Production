<?php

namespace App\Models;

use Database\Factories\FactoryUnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property int|null $daily_capacity_minutes
 * @property int $shift_count
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, FactoryUnitCalendar> $calendars
 * @property-read int|null $calendars_count
 * @property-read Collection<int, Location> $locations
 * @property-read int|null $locations_count
 *
 * @method static \Database\Factories\FactoryUnitFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FactoryUnit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FactoryUnit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FactoryUnit onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FactoryUnit query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FactoryUnit whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FactoryUnit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FactoryUnit whereDailyCapacityMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FactoryUnit whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FactoryUnit whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FactoryUnit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FactoryUnit whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FactoryUnit whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FactoryUnit whereShiftCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FactoryUnit whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FactoryUnit withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FactoryUnit withoutTrashed()
 *
 * @mixin \Eloquent
 */
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
     * @return HasMany<FactoryUnitCalendar, $this>
     */
    public function calendars(): HasMany
    {
        return $this->hasMany(FactoryUnitCalendar::class);
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
