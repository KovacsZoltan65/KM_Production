<?php

namespace App\Repositories\Admin;

use App\Models\StockMovement;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StockMovementRepository extends AbstractAdminRepository implements StockMovementRepositoryInterface
{
    protected string $modelClass = StockMovement::class;

    protected array $sortable = ['id', 'item_id', 'quantity', 'movement_type', 'performed_at'];

    protected array $with = ['item', 'itemBatch', 'itemInstance', 'fromLocation', 'toLocation', 'performer'];

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->query();

        if ($filters['movement_type'] ?? null) {
            $query->where('movement_type', $filters['movement_type']);
        }

        if ($filters['item_id'] ?? null) {
            $query->where('item_id', $filters['item_id']);
        }

        if ($filters['location_id'] ?? null) {
            $query->where(function ($query) use ($filters): void {
                $query
                    ->where('from_location_id', $filters['location_id'])
                    ->orWhere('to_location_id', $filters['location_id']);
            });
        }

        if ($filters['date_from'] ?? null) {
            $query->whereDate('performed_at', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] ?? null) {
            $query->whereDate('performed_at', '<=', $filters['date_to']);
        }

        $sort = \in_array($filters['sort'] ?? null, $this->sortable, true) ? (string) $filters['sort'] : 'performed_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction)->paginate($perPage)->withQueryString();
    }
}
