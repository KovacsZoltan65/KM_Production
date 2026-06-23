<?php

namespace App\Repositories\Contracts;

use App\Models\CustomerOrder;

interface CustomerOrderRepositoryInterface extends AdminRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $items
     */
    public function createWithItems(array $attributes, array $items): CustomerOrder;

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $items
     */
    public function updateWithItems(CustomerOrder $customerOrder, array $attributes, array $items): CustomerOrder;

    public function findForShow(CustomerOrder $customerOrder): CustomerOrder;

    public function confirm(CustomerOrder $customerOrder): CustomerOrder;

    public function cancel(CustomerOrder $customerOrder): CustomerOrder;
}
