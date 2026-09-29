<?php

namespace App\Support\Procurement;

use JsonSerializable;

/** Immutable sections contain scalars, scalar lists and immutable value envelopes only. */
final readonly class SupplierOption implements JsonSerializable
{
    /**
     * @param  array{id: int, code: string, name: string, is_active: bool, deleted_at: SupplierOptionValue}  $supplier
     * @param  array{id: int, supplier_item_code: SupplierOptionValue, is_active: bool, is_approved: bool, is_preferred: bool, priority: int, valid_from: SupplierOptionValue, valid_until: SupplierOptionValue, updated_at: SupplierOptionValue}  $relationship
     * @param  array{is_eligible: bool, reasons: list<string>}  $eligibility
     * @param  array{is_usable: bool, objectively_feasible: SupplierOptionValue, scope: string, reasons: list<string>}  $requirementFit
     * @param  array{minimum_order_quantity: SupplierOptionValue, order_multiple: SupplierOptionValue, strategy: SupplierOptionValue, effective_order_quantity: SupplierOptionValue, excess_quantity: SupplierOptionValue, purchase_unit: SupplierOptionValue, conversion_factor: SupplierOptionValue}  $ordering
     * @param  array{lead_time_days: SupplierOptionValue, expected_date: SupplierOptionValue, date_fit: SupplierOptionValue}  $delivery
     * @param  array{reference_unit_price: SupplierOptionValue, currency: SupplierOptionValue, price_basis: SupplierOptionValue, estimated_reference_value: SupplierOptionValue}  $commercial
     * @param  array{missing: list<array{field: string, reason: string}>, inconsistent: list<array{field: string, reason: string}>, restricted: list<string>}  $dataQuality
     */
    public function __construct(
        public array $supplier,
        public array $relationship,
        public array $eligibility,
        public array $requirementFit,
        public array $ordering,
        public array $delivery,
        public array $commercial,
        public array $dataQuality,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'supplier' => $this->supplier,
            'relationship' => $this->relationship,
            'eligibility' => $this->eligibility,
            'requirement_fit' => $this->requirementFit,
            'ordering' => $this->ordering,
            'delivery' => $this->delivery,
            'commercial' => $this->commercial,
            'data_quality' => $this->dataQuality,
        ];
    }
}
