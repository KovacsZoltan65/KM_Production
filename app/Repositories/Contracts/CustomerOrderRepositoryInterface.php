<?php

namespace App\Repositories\Contracts;

use App\Models\CustomerOrder;

interface CustomerOrderRepositoryInterface extends AdminRepositoryInterface
{
    /**
     * Létrehozza a rendelést és annak tételsorait egyetlen műveleti egységben.
     *
     * @param  array<string, mixed>  $attributes  A rendelés fejlécadatai.
     * @param  list<array{item_id: int, quantity: int|float|string, unit: string,
     *     notes: string|null}>  $items  A létrehozandó tételsorok.
     */
    public function createWithItems(array $attributes, array $items): CustomerOrder;

    /**
     * Frissíti a rendelést, és a megadott készletre cseréli a tételsorait.
     *
     * @param  array<string, mixed>  $attributes  A módosítandó fejlécadatok.
     * @param  list<array{item_id: int, quantity: int|float|string, unit: string,
     *     notes: string|null}>  $items  Az új tételsorok.
     */
    public function updateWithItems(CustomerOrder $customerOrder, array $attributes, array $items): CustomerOrder;

    public function findForShow(CustomerOrder $customerOrder): CustomerOrder;

    public function confirm(CustomerOrder $customerOrder): CustomerOrder;

    public function cancel(CustomerOrder $customerOrder): CustomerOrder;
}
