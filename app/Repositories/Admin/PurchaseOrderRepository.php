<?php

namespace App\Repositories\Admin;

use App\Models\PurchaseOrder;
use App\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class PurchaseOrderRepository extends AbstractAdminRepository implements PurchaseOrderRepositoryInterface
{
    protected string $modelClass = PurchaseOrder::class;

    protected array $searchable = ['order_number', 'notes'];

    protected array $sortable = ['id', 'order_number', 'supplier_id', 'status', 'ordered_at', 'expected_delivery_date', 'created_at'];

    protected array $with = ['supplier', 'purchaseRequisition'];

    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->query()->withCount('items');

        if (($filters['status'] ?? null) !== null) {
            $query->where('status', $filters['status']);
        }

        if (($filters['supplier_id'] ?? null) !== null) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('order_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn (Builder $supplierQuery) => $supplierQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%"));
            });
        }

        $sort = in_array($filters['sort'] ?? null, $this->sortable, true) ? (string) $filters['sort'] : 'id';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($sort, $direction)->paginate($perPage)->withQueryString();
    }

    public function findForShow(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        return $purchaseOrder->load([
            'supplier',
            'creator',
            'purchaseRequisition',
            'items.item',
            'items.purchaseRequisitionItem.purchaseRequisition',
        ])->loadCount('items');
    }
}
