<?php

namespace App\Models;

use Database\Factories\OperationSequenceStepFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
