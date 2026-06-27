<?php

namespace App\Models;

use Database\Factories\FactoryUnitCalendarFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'factory_unit_id',
    'weekday',
    'work_start',
    'work_end',
    'break_minutes',
    'is_working_day',
])]
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
     * @return array<string, string>
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
