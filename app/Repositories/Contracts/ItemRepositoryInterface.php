<?php

namespace App\Repositories\Contracts;

use App\Models\Item;
use Illuminate\Support\Collection;

interface ItemRepositoryInterface extends AdminRepositoryInterface
{
    /**
     * Visszaadja a vevői rendeléshez választható cikkeket.
     *
     * @return Collection<int, Item>
     */
    public function orderableOptions(): Collection;

    /**
     * Visszaadja a megadott azonosítók közül a rendelhető cikkek azonosítóit.
     *
     * @param  list<int>  $itemIds
     * @return Collection<int, int>
     */
    public function orderableItemIds(array $itemIds): Collection;
}
