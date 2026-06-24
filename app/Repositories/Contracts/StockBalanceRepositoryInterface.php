<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface StockBalanceRepositoryInterface extends AdminRepositoryInterface
{
    public function totalQuantityForItem(int $itemId): float;

    /**
     * @return Collection<int, \App\Models\StockBalance>
     */
    public function balancesForReservation(int $itemId): Collection;
}
