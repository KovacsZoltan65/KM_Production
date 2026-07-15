<?php

namespace App\Models;

use Database\Factories\FactoryUnitCalendarFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable([
    'factory_unit_id',
    'weekday',
    'work_start',
    'work_end',
    'break_minutes',
    'is_working_day',
])]
/**
 * A gyáregység hétköznapi kapacitásnaptára.
 *
 * @property int $id
 * @property int $factory_unit_id
 * @property int $weekday
 * @property string $work_start
 * @property string $work_end
 * @property int $break_minutes
 * @property bool $is_working_day
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read FactoryUnit $factoryUnit
 */
class FactoryUnitCalendar extends Model
{
    /** @use HasFactory<FactoryUnitCalendarFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<FactoryUnit, $this>
     */
    public function factoryUnit(): BelongsTo
    {
        return $this->belongsTo(FactoryUnit::class);
    }

    /**
     * @return array{weekday: 'integer', break_minutes: 'integer',
     *     is_working_day: 'boolean'}
     */
    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'break_minutes' => 'integer',
            'is_working_day' => 'boolean',
        ];
    }
}
