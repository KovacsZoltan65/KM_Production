<?php

namespace App\Support\Procurement;

use Illuminate\Contracts\Support\Arrayable;

/** Immutable, non-persisted common Supplier candidate for one PR. */
final readonly class SupplierSelectionCandidate implements Arrayable
{
    /**
     * @param list<array{
     *     item_supplier_id: int,
     *     item_id: int,
     *     item_number: string,
     *     item_name: string,
     *     preferred: bool,
     *     priority: int,
     *     lead_time_days: int|null,
     *     unit_price: string|null,
     *     currency: string|null,
     *     purchase_unit: string,
     *     conversion_factor: string,
     *     minimum_order_quantity: string|null,
     *     order_multiple: string|null,
     *     valid_from: string|null,
     *     valid_until: string|null
     * }> $sources
     */
    public function __construct(
        public int $supplierId,
        public string $supplierCode,
        public string $supplierName,
        public array $sources,
    ) {}

    /**
     * @return array{
     *     supplier_id: int,
     *     supplier_code: string,
     *     supplier_name: string,
     *     sources: list<array{
     *         item_supplier_id: int,
     *         item_id: int,
     *         item_number: string,
     *         item_name: string,
     *         preferred: bool,
     *         priority: int,
     *         lead_time_days: int|null,
     *         unit_price: string|null,
     *         currency: string|null,
     *         purchase_unit: string,
     *         conversion_factor: string,
     *         minimum_order_quantity: string|null,
     *         order_multiple: string|null,
     *         valid_from: string|null,
     *         valid_until: string|null
     *     }>
     * }
     */
    public function toArray(): array
    {
        return [
            'supplier_id' => $this->supplierId,
            'supplier_code' => $this->supplierCode,
            'supplier_name' => $this->supplierName,
            'sources' => $this->sources,
        ];
    }
}
