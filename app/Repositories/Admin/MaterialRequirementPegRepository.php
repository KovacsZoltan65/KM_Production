<?php

namespace App\Repositories\Admin;

use App\Models\MaterialRequirement;
use App\Models\MaterialRequirementPeg;
use App\Repositories\Contracts\MaterialRequirementPegRepositoryInterface;
use Illuminate\Support\Collection;

class MaterialRequirementPegRepository implements MaterialRequirementPegRepositoryInterface
{
    public function requirementIdsForItems(array $itemIds): array
    {
        return MaterialRequirement::query()
            ->whereIn('required_item_id', $itemIds)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    public function lockRequirements(array $requirementIds): Collection
    {
        return MaterialRequirement::query()
            ->whereIn('id', $requirementIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    public function replace(array $requirementIds, array $rows): void
    {
        MaterialRequirementPeg::query()->whereIn('material_requirement_id', $requirementIds)->delete();

        if ($rows !== []) {
            MaterialRequirementPeg::query()->insert($rows);
        }
    }
}
