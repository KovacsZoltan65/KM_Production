<?php

namespace App\Support\Procurement;

use App\Enums\PurchaseRequisitionExecutionReadinessReason;
use Illuminate\Contracts\Support\Arrayable;

/** Immutable structured reason; localization belongs to the presentation layer. */
final readonly class ExecutionReadinessReason implements Arrayable
{
    /**
     * @param  array<string, int|string>  $parameters
     */
    public function __construct(
        public PurchaseRequisitionExecutionReadinessReason $code,
        public ?int $purchaseRequisitionItemId = null,
        public ?int $itemId = null,
        public ?string $itemNumber = null,
        public array $parameters = [],
    ) {}

    public function uniqueKey(): string
    {
        return $this->code->value.'|'.($this->purchaseRequisitionItemId ?? 'pr');
    }

    /**
     * @return array{
     *     code: string,
     *     purchase_requisition_item_id: int|null,
     *     item_id: int|null,
     *     item_number: string|null,
     *     parameters: array<string, int|string>
     * }
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code->value,
            'purchase_requisition_item_id' => $this->purchaseRequisitionItemId,
            'item_id' => $this->itemId,
            'item_number' => $this->itemNumber,
            'parameters' => $this->parameters,
        ];
    }
}
