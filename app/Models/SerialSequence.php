<?php

namespace App\Models;

use Database\Factories\SerialSequenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $prefix
 * @property int $year
 * @property int $last_number
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\SerialSequenceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialSequence newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialSequence newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialSequence query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialSequence whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialSequence whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialSequence whereLastNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialSequence wherePrefix($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialSequence whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SerialSequence whereYear($value)
 * @mixin \Eloquent
 */
#[Fillable([
    'prefix',
    'year',
    'last_number',
])]
class SerialSequence extends Model
{
    /** @use HasFactory<SerialSequenceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'last_number' => 'integer',
        ];
    }
}
