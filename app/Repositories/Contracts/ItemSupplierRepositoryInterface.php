<?php

namespace App\Repositories\Contracts;

use App\Models\ItemSupplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ItemSupplierRepositoryInterface extends AdminRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, ItemSupplier>
     */
    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator;

    /** Zárolja az Itemet a preferred source váltás sorosításához. */
    public function lockItem(int $itemId): void;

    /** Az adott Item többi aktív preferred source rekordját visszaállítja. */
    public function clearActivePreferredForItem(int $itemId, ?int $exceptId = null): void;

    /**
     * @return Collection<int, ItemSupplier>
     */
    public function activeApprovedForItem(int $itemId): Collection;

    /**
     * @return Collection<int, array{id: int, item_number: string, name: string, unit: string}>
     */
    public function itemOptions(int $limit = 500): Collection;

    /**
     * @return Collection<int, array{id: int, code: string, name: string}>
     */
    public function supplierOptions(int $limit = 500): Collection;
}
