<?php

namespace App\Repositories\Contracts;

use App\Models\OperationSequence;

interface OperationSequenceRepositoryInterface extends AdminRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $steps
     */
    public function createWithSteps(array $attributes, array $steps): OperationSequence;

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $steps
     */
    public function updateWithSteps(OperationSequence $operationSequence, array $attributes, array $steps): OperationSequence;
}
