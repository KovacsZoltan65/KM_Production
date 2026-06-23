<?php

namespace App\Services\Admin;

use App\Models\OperationSequence;
use App\Models\User;
use App\Repositories\Contracts\OperationSequenceRepositoryInterface;
use App\Services\AuditLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OperationSequenceAdminService
{
    public function __construct(
        private readonly OperationSequenceRepositoryInterface $repository,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginateForAdminIndex($filters, $perPage);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload, ?User $causer = null): OperationSequence
    {
        $steps = $payload['steps'] ?? [];
        unset($payload['steps']);

        $operationSequence = $this->repository->createWithSteps($payload, $steps);
        $this->auditLogService->log('admin_operation_sequence_created', $operationSequence, [], $causer);

        return $operationSequence;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(OperationSequence $operationSequence, array $payload, ?User $causer = null): OperationSequence
    {
        $steps = $payload['steps'] ?? [];
        unset($payload['steps']);

        $operationSequence = $this->repository->updateWithSteps($operationSequence, $payload, $steps);
        $this->auditLogService->log('admin_operation_sequence_updated', $operationSequence, [], $causer);

        return $operationSequence;
    }

    public function delete(OperationSequence $operationSequence, ?User $causer = null): void
    {
        $this->auditLogService->log('admin_operation_sequence_deleted', $operationSequence, [], $causer);
        $this->repository->delete($operationSequence);
    }
}
