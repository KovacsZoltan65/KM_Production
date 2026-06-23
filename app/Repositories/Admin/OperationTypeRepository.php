<?php

namespace App\Repositories\Admin;

use App\Models\OperationType;
use App\Repositories\Contracts\OperationTypeRepositoryInterface;

class OperationTypeRepository extends AbstractAdminRepository implements OperationTypeRepositoryInterface
{
    protected string $modelClass = OperationType::class;

    protected array $searchable = ['code', 'name', 'description'];

    protected array $sortable = ['id', 'code', 'name', 'is_active', 'created_at'];
}
