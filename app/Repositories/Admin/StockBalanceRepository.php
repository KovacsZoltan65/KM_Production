<?php

namespace App\Repositories\Admin;

use App\Enums\StockReservationStatus;
use App\Models\StockBalance;
use App\Models\StockReservation;
use App\Repositories\Contracts\StockBalanceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StockBalanceRepository extends AbstractAdminRepository implements StockBalanceRepositoryInterface
{
    protected string $modelClass = StockBalance::class;

    protected array $sortable = ['id', 'item_id', 'location_id', 'quantity', 'created_at'];

    protected array $with = ['item', 'location', 'itemBatch'];

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->query();
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->whereHas('item', fn (Builder $itemQuery) => $itemQuery
                        ->where('item_number', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%"))
                    ->orWhereHas('location', fn (Builder $locationQuery) => $locationQuery
                        ->where('code', 'like', "%{$search}%"))
                    ->orWhereHas('itemBatch', fn (Builder $batchQuery) => $batchQuery
                        ->where('batch_number', 'like', "%{$search}%"));
            });
        }

        $sort = in_array($filters['sort'] ?? null, $this->sortable, true) ? (string) $filters['sort'] : 'id';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $paginator = $query->orderBy($sort, $direction)->paginate($perPage)->withQueryString();

        $paginator->getCollection()->transform(function (StockBalance $balance): StockBalance {
            $reserved = StockReservation::query()
                ->where('item_id', $balance->item_id)
                ->where('location_id', $balance->location_id)
                ->where('item_batch_id', $balance->item_batch_id)
                ->where('status', StockReservationStatus::Active->value)
                ->sum('reserved_quantity');

            $balance->setAttribute('reserved_quantity', (float) $reserved);
            $balance->setAttribute('available_quantity', max(0, (float) $balance->quantity - (float) $reserved));
            $balance->setAttribute('unit', $balance->item?->unit);

            return $balance;
        });

        return $paginator;
    }

    public function totalQuantityForItem(int $itemId): float
    {
        return (float) StockBalance::query()
            ->where('item_id', $itemId)
            ->sum('quantity');
    }

    /**
     * @return Collection<int, StockBalance>
     */
    public function balancesForReservation(int $itemId): Collection
    {
        return StockBalance::query()
            ->with(['item', 'location', 'itemBatch'])
            ->where('item_id', $itemId)
            ->where('quantity', '>', 0)
            ->orderBy('id')
            ->get();
    }
}
