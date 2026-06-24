<?php

namespace App\Repositories\Contracts;

use App\Models\StockReservation;
use Illuminate\Support\Collection;

interface StockReservationRepositoryInterface extends AdminRepositoryInterface
{
    public function activeReservedQuantity(
        int $itemId,
        ?int $locationId = null,
        ?int $itemBatchId = null,
        ?int $customerOrderItemId = null,
        ?int $productionOrderId = null
    ): float;

    public function activeReservedQuantityForBalance(int $itemId, int $locationId, ?int $itemBatchId): float;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createReservation(array $attributes): StockReservation;

    public function release(StockReservation $reservation): StockReservation;

    /**
     * @return Collection<int, StockReservation>
     */
    public function activeForProductionOrderItem(int $productionOrderId, int $itemId): Collection;
}
