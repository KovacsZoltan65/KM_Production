<?php

namespace App\Models;

use Database\Factories\OperationSequenceStepFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $operation_sequence_id
 * @property int $step_order
 * @property int $operation_type_id
 * @property int $factory_unit_id
 * @property int $professional_role_id
 * @property int $estimated_duration_minutes
 * @property bool $requires_quality_check
 * @property string|null $instructions
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read FactoryUnit|null $factoryUnit
 * @property-read OperationSequence|null $operationSequence
 * @property-read OperationType|null $operationType
 * @property-read ProfessionalRole|null $professionalRole
 *
 * @method static \Database\Factories\OperationSequenceStepFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationSequenceStep newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationSequenceStep newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationSequenceStep query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationSequenceStep whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationSequenceStep whereEstimatedDurationMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationSequenceStep whereFactoryUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationSequenceStep whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationSequenceStep whereInstructions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationSequenceStep whereOperationSequenceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationSequenceStep whereOperationTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationSequenceStep whereProfessionalRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationSequenceStep whereRequiresQualityCheck($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationSequenceStep whereStepOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OperationSequenceStep whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'operation_sequence_id',
    'step_order',
    'operation_type_id',
    'factory_unit_id',
    'professional_role_id',
    'estimated_duration_minutes',
    'requires_quality_check',
    'instructions',
])]
class OperationSequenceStep extends Model
{
    /** @use HasFactory<OperationSequenceStepFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<OperationSequence, $this>
     */
    public function operationSequence(): BelongsTo
    {
        return $this->belongsTo(OperationSequence::class);
    }

    /**
     * @return BelongsTo<OperationType, $this>
     */
    public function operationType(): BelongsTo
    {
        return $this->belongsTo(OperationType::class);
    }

    /**
     * @return BelongsTo<FactoryUnit, $this>
     */
    public function factoryUnit(): BelongsTo
    {
        return $this->belongsTo(FactoryUnit::class);
    }

    /**
     * @return BelongsTo<ProfessionalRole, $this>
     */
    public function professionalRole(): BelongsTo
    {
        return $this->belongsTo(ProfessionalRole::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'step_order' => 'integer',
            'estimated_duration_minutes' => 'integer',
            'requires_quality_check' => 'boolean',
        ];
    }
}
