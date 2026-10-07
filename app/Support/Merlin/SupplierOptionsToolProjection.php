<?php

namespace App\Support\Merlin;

use App\Support\Procurement\SupplierOption;
use App\Support\Procurement\SupplierOptionResult;
use App\Support\Procurement\SupplierOptionValue;

/** Explicit disclosure list; domain jsonSerialize additions cannot become tool-visible. */
final class SupplierOptionsToolProjection
{
    /** @return array<string, mixed> */
    public function project(SupplierOptionResult $result): array
    {
        $input = $result->requirement;
        $provenance = $input->provenance;

        return [
            'schema_version' => $result->schemaVersion,
            'item' => $this->fields($result->item, ['id', 'item_number', 'name', 'item_type', 'unit', 'is_active']),
            'requirement' => [
                'required_quantity' => $input->requiredQuantity,
                'required_date' => $this->value($input->requiredDate === null
                    ? SupplierOptionValue::unknown('MISSING_REQUIRED_DATE') : SupplierOptionValue::known($input->requiredDate)),
                'unit' => $input->unit,
                'provenance' => [
                    'source_type' => $provenance->sourceType,
                    'source_id' => $provenance->sourceId,
                    'quantity_basis' => $provenance->quantityBasis,
                    'observed_at' => $provenance->observedAt,
                    'netting_scope' => $this->value($provenance->nettingScope === null
                        ? SupplierOptionValue::notApplicable('NOT_NETTING_SOURCE') : SupplierOptionValue::known($provenance->nettingScope)),
                ],
            ],
            'evaluation_date' => $input->evaluationDate,
            'evaluated_at' => $result->evaluatedAt,
            'options' => array_map($this->option(...), $result->options),
        ];
    }

    /** @return array<string, mixed> */
    private function option(SupplierOption $option): array
    {
        return [
            'supplier' => $this->fields($option->supplier, ['id', 'code', 'name', 'is_active', 'deleted_at']),
            'relationship' => $this->fields($option->relationship, ['id', 'supplier_item_code', 'is_active', 'is_approved', 'is_preferred', 'priority', 'valid_from', 'valid_until', 'updated_at']),
            'eligibility' => $this->fields($option->eligibility, ['is_eligible', 'reasons']),
            'requirement_fit' => $this->fields($option->requirementFit, ['is_usable', 'objectively_feasible', 'scope', 'reasons']),
            'ordering' => $this->fields($option->ordering, ['minimum_order_quantity', 'order_multiple', 'strategy', 'effective_order_quantity', 'excess_quantity', 'purchase_unit', 'conversion_factor']),
            'delivery' => $this->fields($option->delivery, ['lead_time_days', 'expected_date', 'date_fit']),
            'commercial' => $this->fields($option->commercial, ['reference_unit_price', 'currency', 'price_basis', 'estimated_reference_value']),
            'data_quality' => [
                'missing' => array_map(fn (array $entry): array => $this->fields($entry, ['field', 'reason']), $option->dataQuality['missing']),
                'inconsistent' => array_map(fn (array $entry): array => $this->fields($entry, ['field', 'reason']), $option->dataQuality['inconsistent']),
                'restricted' => $option->dataQuality['restricted'],
            ],
        ];
    }

    /** @param array<string, mixed> $data @param list<string> $keys @return array<string, mixed> */
    private function fields(array $data, array $keys): array
    {
        $projection = [];
        foreach ($keys as $key) {
            $value = $data[$key];
            $projection[$key] = $value instanceof SupplierOptionValue ? $this->value($value) : $value;
        }

        return $projection;
    }

    /** @return array{state: string, value: string|int|bool|null, reason: ?string} */
    private function value(SupplierOptionValue $value): array
    {
        return ['state' => $value->state, 'value' => $value->value, 'reason' => $value->reason];
    }
}
