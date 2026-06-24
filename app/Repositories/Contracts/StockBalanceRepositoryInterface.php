<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface StockBalanceRepositoryInterface extends AdminRepositoryInterface
{
    public function totalQuantityForItem(int $itemId): float;

    public function increaseQuantity(int $itemId, int $locationId, ?int $itemBatchId, float $quantity): void;

    /**
     * @return Collection<int, \App\Models\StockBalance>
     */
    public function balancesForReservation(int $itemId): Collection;
}
