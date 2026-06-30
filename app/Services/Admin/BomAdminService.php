<?php

namespace App\Services\Admin;

use App\Models\Bom;
use App\Models\User;
use App\Repositories\Contracts\BomRepositoryInterface;
use App\Services\AuditLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BomAdminService
{
    public function __construct(
        private readonly BomRepositoryInterface $repository,
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
     * @param  mixed  $causer
     */
    public function create(array $payload, ?User $causer = null): Bom
    {
        $items = $payload['items'] ?? [];
        unset($payload['items']);

        $bom = $this->repository->createWithItems($payload, $items);
        $this->auditLogService->log('admin_bom_created', $bom, [], $causer);

        return $bom;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  mixed  $causer
     */
    public function update(Bom $bom, array $payload, ?User $causer = null): Bom
    {
        $items = $payload['items'] ?? [];
        unset($payload['items']);

        $bom = $this->repository->updateWithItems($bom, $payload, $items);
        $this->auditLogService->log('admin_bom_updated', $bom, [], $causer);

        return $bom;
    }

    /**
     * @param  mixed  $causer
     */
    public function delete(Bom $bom, ?User $causer = null): void
    {
        $this->auditLogService->log('admin_bom_deleted', $bom, [], $causer);
        $this->repository->delete($bom);
    }
}
