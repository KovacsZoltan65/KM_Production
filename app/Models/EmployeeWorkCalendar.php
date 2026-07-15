<?php

namespace App\Models;

use Database\Factories\EmployeeWorkCalendarFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable([
    'employee_id',
    'weekday',
    'work_start',
    'work_end',
    'break_minutes',
])]
/**
 * A dolgozó hétköznapi munkaidő-beosztása.
 *
 * @property int $id
 * @property int $employee_id
 * @property int $weekday
 * @property string $work_start
 * @property string $work_end
 * @property int $break_minutes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Employee $employee
 */
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
     * @return array{weekday: 'integer', break_minutes: 'integer'}
     */
    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'break_minutes' => 'integer',
        ];
    }
}
