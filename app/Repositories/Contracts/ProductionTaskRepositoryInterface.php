<?php

namespace App\Repositories\Contracts;

use App\Models\ProductionTask;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ProductionTaskRepositoryInterface extends AdminRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForExecution(array $filters, int $perPage = 10): LengthAwarePaginator;

    public function findForShow(ProductionTask $productionTask): ProductionTask;

    /**
     * @return Collection<int, ProductionTask>
     */
    public function readyAndActiveForShopFloor(?int $employeeId = null): Collection;
}
