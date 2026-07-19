<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Repositories\Contracts\AdminRepositoryInterface;
use App\Services\AuditLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

/**
 * Az egyszerű adminisztrációs CRUD-szolgáltatások közös folyamatait biztosítja.
 *
 * A perzisztenciát repository-ra delegálja, és egységes auditbejegyzést készít;
 * összetett üzleti workflow-kat a konkrét szolgáltatások kezelnek.
 */
abstract class AbstractAdminService
{
    public function __construct(
        protected AdminRepositoryInterface $repository,
        protected AuditLogService $auditLogService,
    ) {}

    /**
     * Delegálja a szűrt és lapozott adminisztrációs listázást.
     *
     * @param  array<string, mixed>  $filters  Az alkalmazandó listaoldali szűrők.
     * @return LengthAwarePaginator<int, covariant Model> A lapozott modellpéldányok.
     */
    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginateForAdminIndex($filters, $perPage);
    }

    /**
     * Normalizálja, létrehozza és auditnaplózza a modellt.
     *
     * @param  array<string, mixed>  $attributes  A validált modellattribútumok.
     * @param  User|null  $causer  A műveletet végrehajtó felhasználó.
     */
    public function create(array $attributes, ?User $causer = null): Model
    {
        $attributes = $this->normalizeAttributes($attributes);
        $model = $this->repository->create($attributes);
        $this->auditLogService->log($this->createdEvent(), $model, [], $causer);

        return $model;
    }

    /**
     * Normalizálja, frissíti és auditnaplózza a modellt.
     *
     * @param  Model  $model  A frissítendő modell.
     * @param  array<string, mixed>  $attributes  A validált modellattribútumok.
     * @param  User|null  $causer  A műveletet végrehajtó felhasználó.
     * @return Model A frissített modell.
     */
    public function update(Model $model, array $attributes, ?User $causer = null): Model
    {
        $attributes = $this->normalizeAttributes($attributes);
        $model = $this->repository->update($model, $attributes);
        $this->auditLogService->log($this->updatedEvent(), $model, [], $causer);

        return $model;
    }

    /**
     * Auditnaplózza, majd a repository-n keresztül törli a modellt.
     *
     * @param  Model  $model  A törlendő modell.
     */
    public function delete(Model $model, ?User $causer = null): void
    {
        $this->auditLogService->log($this->deletedEvent(), $model, [], $causer);
        $this->repository->delete($model);
    }

    abstract protected function createdEvent(): string;

    abstract protected function updatedEvent(): string;

    abstract protected function deletedEvent(): string;

    /**
     * Előkészíti az attribútumokat a repository számára.
     *
     * A konkrét szolgáltatások felülírhatják a normalizálási szabályokat.
     *
     * @param  array<string, mixed>  $attributes  A validált attribútumok.
     * @return array<string, mixed> A repository-nak átadható attribútumok.
     */
    protected function normalizeAttributes(array $attributes): array
    {
        return $attributes;
    }
}
