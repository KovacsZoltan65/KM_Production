<?php

namespace App\Repositories\Admin;

use App\Models\OperationSequence;
use App\Repositories\Contracts\OperationSequenceRepositoryInterface;
use Illuminate\Support\Facades\DB;

class OperationSequenceRepository extends AbstractAdminRepository implements OperationSequenceRepositoryInterface
{
    protected string $modelClass = OperationSequence::class;

    protected array $searchable = ['name', 'description'];

    protected array $sortable = ['id', 'item_id', 'version', 'name', 'is_active', 'created_at'];

    protected array $with = [
        'item',
        'steps.operationType',
        'steps.factoryUnit',
        'steps.professionalRole',
    ];

    public function createWithSteps(array $attributes, array $steps): OperationSequence
    {
        return DB::transaction(function () use ($attributes, $steps): OperationSequence {
            /** @var OperationSequence $operationSequence */
            $operationSequence = $this->query()->create($attributes);
            $operationSequence->steps()->createMany($steps);

            return $operationSequence->load([
                'item',
                'steps.operationType',
                'steps.factoryUnit',
                'steps.professionalRole',
            ]);
        });
    }

    public function updateWithSteps(OperationSequence $operationSequence, array $attributes, array $steps): OperationSequence
    {
        return DB::transaction(function () use ($operationSequence, $attributes, $steps): OperationSequence {
            $operationSequence->update($attributes);
            $operationSequence->steps()->delete();
            $operationSequence->steps()->createMany($steps);

            return $operationSequence->refresh()->load([
                'item',
                'steps.operationType',
                'steps.factoryUnit',
                'steps.professionalRole',
            ]);
        });
    }
}
