<?php

namespace App\Services\Admin;

use App\Models\OperationSequence;
use App\Models\User;
use App\Repositories\Contracts\OperationSequenceRepositoryInterface;
use App\Services\AuditLogService;
use App\Services\BusinessCacheInvalidator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * A műveletsorok és verziózott lépéseik adminisztrációs folyamatait koordinálja.
 *
 * Az összetett mentést repository-ra delegálja, a változásokat auditnaplózza.
 */
class OperationSequenceAdminService
{
    public function __construct(
        private readonly OperationSequenceRepositoryInterface $repository,
        private readonly AuditLogService $auditLogService,
        private readonly BusinessCacheInvalidator $cacheInvalidator,
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

        $operationSequence = DB::transaction(function () use ($payload, $steps, $causer): OperationSequence {
            $operationSequence = $this->repository->createWithSteps($payload, $steps);
            $this->auditLogService->logCreated('admin_operation_sequence_created', $operationSequence, $causer, [
                'steps_count' => \count($steps),
            ]);

            return $operationSequence;
        });
        $this->cacheInvalidator->productionChanged();

        return $operationSequence;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(OperationSequence $operationSequence, array $payload, ?User $causer = null): OperationSequence
    {
        $steps = $payload['steps'] ?? [];
        unset($payload['steps']);

        $operationSequence = DB::transaction(function () use ($operationSequence, $payload, $steps, $causer): OperationSequence {
            $original = $operationSequence->getRawOriginal();
            $operationSequence = $this->repository->updateWithSteps($operationSequence, $payload, $steps);
            $this->auditLogService->logUpdated('admin_operation_sequence_updated', $operationSequence, $original, $causer, [
                'steps_synchronized' => true,
                'steps_count' => \count($steps),
            ]);

            return $operationSequence;
        });
        $this->cacheInvalidator->productionChanged();

        return $operationSequence;
    }

    public function delete(OperationSequence $operationSequence, ?User $causer = null): void
    {
        $this->auditLogService->log('admin_operation_sequence_deleted', $operationSequence, [], $causer);
        $this->repository->delete($operationSequence);
        $this->cacheInvalidator->productionChanged();
    }
}
