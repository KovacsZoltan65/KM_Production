<?php

namespace App\Repositories\Admin;

use App\Models\Bom;
use App\Repositories\Contracts\BomRepositoryInterface;
use Illuminate\Support\Facades\DB;

class BomRepository extends AbstractAdminRepository implements BomRepositoryInterface
{
    protected string $modelClass = Bom::class;

    protected array $searchable = ['name', 'description'];

    protected array $sortable = ['id', 'item_id', 'version', 'name', 'is_active', 'created_at'];

    protected array $with = ['item', 'bomItems.item'];

    public function createWithItems(array $attributes, array $items): Bom
    {
        return DB::transaction(function () use ($attributes, $items): Bom {
            /** @var Bom $bom */
            $bom = $this->query()->create($attributes);
            $bom->bomItems()->createMany($items);

            return $bom->load(['item', 'bomItems.item']);
        });
    }

    public function updateWithItems(Bom $bom, array $attributes, array $items): Bom
    {
        return DB::transaction(function () use ($bom, $attributes, $items): Bom {
            $bom->update($attributes);
            $bom->bomItems()->delete();
            $bom->bomItems()->createMany($items);

            return $bom->refresh()->load(['item', 'bomItems.item']);
        });
    }
}
