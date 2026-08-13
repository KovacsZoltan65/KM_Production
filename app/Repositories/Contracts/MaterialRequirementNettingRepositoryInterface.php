<?php

namespace App\Repositories\Contracts;

use App\Models\MaterialRequirement;
use Illuminate\Support\Collection;

interface MaterialRequirementNettingRepositoryInterface
{
    /** @return Collection<int, MaterialRequirement> */
    public function requirements(): Collection;

    /**
     * Returns free physical stock by Item in Item base unit.
     *
     * @param  list<int>  $itemIds
     * @return array<int, string>
     */
    public function usableOnHandByItem(array $itemIds): array;

    /**
     * Returns dated, firm PO remainder pools in Item base unit.
     *
     * @param  list<int>  $itemIds
     * @return array<int, list<array{id: int, available_at: string, ordered_quantity: string, received_quantity: string}>>
     */
    public function firmIncomingByItem(array $itemIds): array;
}
