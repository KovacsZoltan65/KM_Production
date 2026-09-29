<?php

declare(strict_types=1);

namespace App\Support\Procurement;

/** Assertion from a trusted application producer, not proof of authority by itself. */
final readonly class ProcurementRequirementProvenance
{
    public function __construct(
        public string $sourceType,
        public int $sourceId,
        public string $quantityBasis,
        public string $observedAt,
        public ?string $nettingScope = null,
    ) {
        $basis = match ($sourceType) {
            'material_requirement_netting' => 'net_requirement',
            'supply_proposal' => 'proposal_planned',
            'purchase_requisition_item' => 'pr_planned',
            default => null,
        };
        if ($basis === null || $basis !== $quantityBasis || $sourceId <= 0) {
            ProcurementInputValidation::fail('provenance');
        }
        if ($sourceType === 'material_requirement_netting'
            ? ! in_array($nettingScope, ['all_requirements', 'complete_item_requirements'], true)
            : $nettingScope !== null) {
            ProcurementInputValidation::fail('provenance.netting_scope');
        }
        ProcurementInputValidation::timestamp($observedAt, 'provenance.observed_at');
    }
}
