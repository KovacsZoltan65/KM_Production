<?php

namespace App\Repositories\Contracts;

use App\Models\SupplyProposal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface SupplyProposalRepositoryInterface extends AdminRepositoryInterface
{
    /** @return LengthAwarePaginator<int, SupplyProposal> */
    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator;

    public function findLocked(int $id): SupplyProposal;

    /** @return Collection<int, array{id: int, item_number: string, name: string, unit: string}> */
    public function itemOptions(int $limit = 500): Collection;

    /** @return Collection<int, array{item_id: int, supplier_id: int, code: string, name: string}> */
    public function usableSupplierOptions(int $limit = 1000): Collection;

    public function hasUsableProcurementSource(int $itemId, int $supplierId): bool;
}
