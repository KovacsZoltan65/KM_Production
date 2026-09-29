<?php

declare(strict_types=1);

namespace App\Support\Procurement;

/** Already authoritative procurement need in the Item base unit; never netted here. */
final readonly class ProcurementRequirementInput
{
    public function __construct(
        public int $itemId,
        public string $requiredQuantity,
        public ?string $requiredDate,
        public string $unit,
        public string $evaluationDate,
        public ProcurementRequirementProvenance $provenance,
    ) {
        if ($itemId <= 0 || trim($unit) === '') {
            ProcurementInputValidation::fail('requirement');
        }
        $quantity = ProcurementDecimal::toScaledInteger($requiredQuantity, 3, 'required_quantity');
        if ($quantity < 0 || ProcurementDecimal::fromThousandths($quantity) !== $requiredQuantity) {
            ProcurementInputValidation::fail('required_quantity');
        }
        ProcurementInputValidation::date($evaluationDate, 'evaluation_date');
        if ($requiredDate !== null) {
            ProcurementInputValidation::date($requiredDate, 'required_date');
        }
    }
}
