<?php

namespace App\Models;

use Database\Factories\ProfessionalRoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Employee> $employees
 * @property-read int|null $employees_count
 * @method static \Database\Factories\ProfessionalRoleFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfessionalRole newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfessionalRole newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfessionalRole onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfessionalRole query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfessionalRole whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfessionalRole whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfessionalRole whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfessionalRole whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfessionalRole whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfessionalRole whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfessionalRole whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfessionalRole whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfessionalRole withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfessionalRole withoutTrashed()
 * @mixin \Eloquent
 */
#[Fillable([
    'code',
    'name',
    'description',
    'is_active',
])]
class ProfessionalRole extends Model
{
    /** @use HasFactory<ProfessionalRoleFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return HasMany<Employee, $this>
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
