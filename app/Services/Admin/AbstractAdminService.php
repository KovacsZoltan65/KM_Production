<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Repositories\Contracts\AdminRepositoryInterface;
use App\Services\AuditLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

abstract class AbstractAdminService
{
    public function __construct(
        protected AdminRepositoryInterface $repository,
        protected AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginateForAdminIndex($filters, $perPage);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes, ?User $causer = null): Model
    {
        $model = $this->repository->create($attributes);
        $this->auditLogService->log($this->createdEvent(), $model, [], $causer);

        return $model;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Model $model, array $attributes, ?User $causer = null): Model
    {
        $model = $this->repository->update($model, $attributes);
        $this->auditLogService->log($this->updatedEvent(), $model, [], $causer);

        return $model;
    }

    public function delete(Model $model, ?User $causer = null): void
    {
        $this->auditLogService->log($this->deletedEvent(), $model, [], $causer);
        $this->repository->delete($model);
    }

    abstract protected function createdEvent(): string;

    abstract protected function updatedEvent(): string;

    abstract protected function deletedEvent(): string;
}
