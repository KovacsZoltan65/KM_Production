<?php

declare(strict_types=1);

namespace App\Support\Procurement;

/** Already authoritative procurement need in the Item base unit; never netted here. */
final readonly class ProcurementRequirementInput
{
    public string $requiredQuantity;

    public function __construct(
        public int $itemId,
        mixed $requiredQuantity,
        public ?string $requiredDate,
        public string $unit,
        public string $evaluationDate,
        public ProcurementRequirementProvenance $provenance,
    ) {
        // A string parameter would coerce floats before validation in non-strict callers.
        if (! is_string($requiredQuantity)) {
            ProcurementInputValidation::fail('required_quantity');
        }
        if ($itemId <= 0 || trim($unit) === '') {
            ProcurementInputValidation::fail('requirement');
        }
        $quantity = ProcurementDecimal::toScaledInteger($requiredQuantity, 3, 'required_quantity');
        if ($quantity < 0 || ProcurementDecimal::fromThousandths($quantity) !== $requiredQuantity) {
            ProcurementInputValidation::fail('required_quantity');
        }
        $this->requiredQuantity = $requiredQuantity;
        ProcurementInputValidation::date($evaluationDate, 'evaluation_date');
        if ($requiredDate !== null) {
            ProcurementInputValidation::date($requiredDate, 'required_date');
        }
    }
}
