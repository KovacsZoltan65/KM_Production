<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\MaterialRequirementRepositoryInterface;
use App\Repositories\Contracts\StockBalanceRepositoryInterface;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use App\Repositories\Contracts\StockReservationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InventoryQueryService
{
    public function __construct(
        private readonly StockBalanceRepositoryInterface $stockBalances,
        private readonly StockMovementRepositoryInterface $stockMovements,
        private readonly StockReservationRepositoryInterface $stockReservations,
        private readonly MaterialRequirementRepositoryInterface $materialRequirements,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateStockBalances(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->stockBalances->paginateForAdminIndex($filters, $perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateStockMovements(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->stockMovements->paginateForAdminIndex($filters, $perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateStockReservations(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->stockReservations->paginateForAdminIndex($filters, $perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateMaterialRequirements(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->materialRequirements->paginateForAdminIndex($filters, $perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateShortages(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->materialRequirements->paginateShortages($filters, $perPage);
    }
}
