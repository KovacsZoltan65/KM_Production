<?php

namespace App\Repositories\Admin;

use App\Enums\StockReservationStatus;
use App\Models\StockReservation;
use App\Repositories\Contracts\StockReservationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class StockReservationRepository extends AbstractAdminRepository implements StockReservationRepositoryInterface
{
    protected string $modelClass = StockReservation::class;

    protected array $sortable = ['id', 'item_id', 'reserved_quantity', 'status', 'reserved_at', 'released_at'];

    protected array $with = ['item', 'location', 'itemBatch', 'customerOrderItem.customerOrder', 'productionOrder', 'reserver'];

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->query();

        if ($filters['status'] ?? null) {
            $query->where('status', $filters['status']);
        }

        $sort = in_array($filters['sort'] ?? null, $this->sortable, true) ? (string) $filters['sort'] : 'reserved_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction)->paginate($perPage)->withQueryString();
    }

    public function activeReservedQuantity(
        int $itemId,
        ?int $locationId = null,
        ?int $itemBatchId = null,
        ?int $customerOrderItemId = null,
        ?int $productionOrderId = null
    ): float {
        $query = StockReservation::query()
            ->where('item_id', $itemId)
            ->where('status', StockReservationStatus::Active->value);

        if ($locationId !== null) {
            $query->where('location_id', $locationId);
        }

        if ($itemBatchId !== null) {
            $query->where('item_batch_id', $itemBatchId);
        }

        if ($customerOrderItemId !== null) {
            $query->where('customer_order_item_id', $customerOrderItemId);
        }

        if ($productionOrderId !== null) {
            $query->where('production_order_id', $productionOrderId);
        }

        return (float) $query->sum('reserved_quantity');
    }

    public function activeReservedQuantityForBalance(int $itemId, int $locationId, ?int $itemBatchId): float
    {
        return (float) StockReservation::query()
            ->where('item_id', $itemId)
            ->where('location_id', $locationId)
            ->where('item_batch_id', $itemBatchId)
            ->where('status', StockReservationStatus::Active->value)
            ->sum('reserved_quantity');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createReservation(array $attributes): StockReservation
    {
        return StockReservation::query()->create($attributes);
    }

    public function release(StockReservation $reservation): StockReservation
    {
        $reservation->update([
            'status' => StockReservationStatus::Released->value,
            'released_at' => now(),
        ]);

        return $reservation->refresh();
    }

    /**
     * @return Collection<int, StockReservation>
     */
    public function activeForProductionOrderItem(int $productionOrderId, int $itemId): Collection
    {
        return StockReservation::query()
            ->where('production_order_id', $productionOrderId)
            ->where('item_id', $itemId)
            ->where('status', StockReservationStatus::Active->value)
            ->get();
    }
}
