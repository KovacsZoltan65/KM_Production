<?php

namespace App\Repositories\Contracts;

use App\Models\MaterialRequirement;
use Illuminate\Support\Collection;

interface MaterialRequirementPegRepositoryInterface
{
    /** @param list<int> $itemIds @return list<int> */
    public function requirementIdsForItems(array $itemIds): array;

    /** @param list<int> $requirementIds @return Collection<int, MaterialRequirement> */
    public function lockRequirements(array $requirementIds): Collection;

    /** @param list<int> $requirementIds @param list<array<string, mixed>> $rows */
    public function replace(array $requirementIds, array $rows): void;
}
