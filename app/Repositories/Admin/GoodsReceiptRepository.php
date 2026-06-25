<?php

namespace App\Repositories\Admin;

use App\Models\GoodsReceipt;
use App\Repositories\Contracts\GoodsReceiptRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class GoodsReceiptRepository extends AbstractAdminRepository implements GoodsReceiptRepositoryInterface
{
    protected string $modelClass = GoodsReceipt::class;

    protected array $searchable = ['receipt_number', 'notes'];

    protected array $sortable = ['id', 'receipt_number', 'status', 'received_at', 'created_at'];

    protected array $with = ['purchaseOrder.supplier', 'receiver'];

    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->query()->withCount('items');

        if (($filters['status'] ?? null) !== null) {
            $query->where('status', $filters['status']);
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('receipt_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('purchaseOrder', fn (Builder $orderQuery) => $orderQuery
                        ->where('order_number', 'like', "%{$search}%"));
            });
        }

        $sort = \in_array($filters['sort'] ?? null, $this->sortable, true) ? (string) $filters['sort'] : 'id';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($sort, $direction)->paginate($perPage)->withQueryString();
    }

    public function findForShow(GoodsReceipt $goodsReceipt): GoodsReceipt
    {
        return $goodsReceipt->load([
            'purchaseOrder.supplier',
            'receiver',
            'items.item',
            'items.itemBatch',
            'items.location',
            'items.purchaseOrderItem',
        ])->loadCount('items');
    }
}
