<?php

namespace App\Support\Procurement;

use JsonSerializable;

final readonly class SupplierOptionResult implements JsonSerializable
{
    public string $schemaVersion;

    /**
     * @param  array{id: int, item_number: string, name: string, item_type: string, unit: string, is_active: bool}  $item
     * @param  list<SupplierOption>  $options
     */
    public function __construct(
        public array $item,
        public ProcurementRequirementInput $requirement,
        public string $evaluatedAt,
        public array $options,
    ) {
        $this->schemaVersion = '0.1';
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $input = $this->requirement;
        $provenance = $input->provenance;

        return [
            'schema_version' => $this->schemaVersion,
            'item' => $this->item,
            'requirement' => [
                'required_quantity' => $input->requiredQuantity,
                'required_date' => $input->requiredDate === null
                    ? SupplierOptionValue::unknown('MISSING_REQUIRED_DATE') : SupplierOptionValue::known($input->requiredDate),
                'unit' => $input->unit,
                'provenance' => [
                    'source_type' => $provenance->sourceType,
                    'source_id' => $provenance->sourceId,
                    'quantity_basis' => $provenance->quantityBasis,
                    'observed_at' => $provenance->observedAt,
                    'netting_scope' => $provenance->nettingScope === null
                        ? SupplierOptionValue::notApplicable('NOT_NETTING_SOURCE') : SupplierOptionValue::known($provenance->nettingScope),
                ],
            ],
            'evaluation_date' => $input->evaluationDate,
            'evaluated_at' => $this->evaluatedAt,
            'options' => $this->options,
        ];
    }
}
