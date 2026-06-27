<?php

namespace App\Models;

use Database\Factories\EmployeeWorkCalendarFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id',
    'weekday',
    'work_start',
    'work_end',
    'break_minutes',
])]
class EmployeeWorkCalendar extends Model
{
    /** @use HasFactory<EmployeeWorkCalendarFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'break_minutes' => 'integer',
        ];
    }
}
