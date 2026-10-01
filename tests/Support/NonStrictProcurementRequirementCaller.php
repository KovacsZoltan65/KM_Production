<?php

namespace Tests\Support;

use App\Support\Procurement\ProcurementRequirementInput;
use App\Support\Procurement\ProcurementRequirementProvenance;

// Intentionally no strict_types: the input boundary must also protect weak callers.
final class NonStrictProcurementRequirementCaller
{
    public static function make(mixed $quantity): ProcurementRequirementInput
    {
        return new ProcurementRequirementInput(
            itemId: 1,
            requiredQuantity: $quantity,
            requiredDate: null,
            unit: 'kg',
            evaluationDate: '2026-09-28',
            provenance: new ProcurementRequirementProvenance(
                'supply_proposal', 1, 'proposal_planned', '2026-09-28T08:00:00Z',
            ),
        );
    }
}
