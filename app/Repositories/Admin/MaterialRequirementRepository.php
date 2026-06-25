<?php

namespace App\Repositories\Admin;

use App\Models\BomItem;
use App\Models\MaterialRequirement;
use App\Models\ProductionOrder;
use App\Repositories\Contracts\MaterialRequirementRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MaterialRequirementRepository extends AbstractAdminRepository implements MaterialRequirementRepositoryInterface
{
    protected string $modelClass = MaterialRequirement::class;

    protected array $sortable = ['id', 'required_item_id', 'required_quantity', 'available_quantity', 'reserved_quantity', 'missing_quantity', 'status'];

    protected array $with = ['customerOrderItem.customerOrder.customer', 'customerOrderItem.productionOrders', 'requiredItem'];

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->filteredQuery($filters);
        $sort = \in_array($filters['sort'] ?? null, $this->sortable, true) ? (string) $filters['sort'] : 'id';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($sort, $direction)->paginate($perPage)->withQueryString();
    }

    public function paginateShortages(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)
            ->where('missing_quantity', '>', 0)
            ->orderByDesc('missing_quantity')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function filteredQuery(array $filters)
    {
        $query = $this->query();

        if ($filters['status'] ?? null) {
            $query->where('status', $filters['status']);
        }

        if ($filters['required_item_id'] ?? null) {
            $query->where('required_item_id', $filters['required_item_id']);
        }

        if ($filters['customer_order_id'] ?? null) {
            $query->whereHas('customerOrderItem', fn ($itemQuery) => $itemQuery->where('customer_order_id', $filters['customer_order_id']));
        }

        return $query;
    }

    public function updateForProductionOrderBomItem(
        ProductionOrder $productionOrder,
        BomItem $bomItem,
        float $requiredQuantity,
        float $availableQuantity,
        float $reservedQuantity,
        float $missingQuantity,
        string $status
    ): MaterialRequirement {
        return MaterialRequirement::query()->updateOrCreate(
            [
                'customer_order_item_id' => $productionOrder->customer_order_item_id,
                'required_item_id' => $bomItem->item_id,
            ],
            [
                'required_quantity' => $requiredQuantity,
                'available_quantity' => $availableQuantity,
                'reserved_quantity' => $reservedQuantity,
                'missing_quantity' => $missingQuantity,
                'unit' => $bomItem->unit,
                'status' => $status,
            ]
        );
    }
}
