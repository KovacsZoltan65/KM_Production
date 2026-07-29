<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Repositories\Contracts\AdminRepositoryInterface;
use App\Services\AuditLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
        $model = DB::transaction(function () use ($attributes, $causer): Model {
            $model = $this->repository->create($attributes);
            $this->auditLogService->logCreated(
                $this->createdEvent(),
                $model,
                $causer,
                $this->createdAuditProperties($model, $attributes),
            );

            return $model;
        });
        $this->afterWrite();

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
        $model = DB::transaction(function () use ($model, $attributes, $causer): Model {
            $original = $model->getRawOriginal();
            $context = $this->captureUpdateAuditContext($model, $attributes);
            $model = $this->repository->update($model, $attributes);
            $this->auditLogService->logUpdated(
                $this->updatedEvent(),
                $model,
                $original,
                $causer,
                $this->updatedAuditProperties($model, $attributes, $context),
            );

            return $model;
        });
        $this->afterWrite();

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
        $this->afterWrite();
    }

    abstract protected function createdEvent(): string;

    abstract protected function updatedEvent(): string;

    abstract protected function deletedEvent(): string;

    protected function afterWrite(): void {}

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function createdAuditProperties(Model $model, array $attributes): array
    {
        return [];
    }

    /**
     * A kapcsolati vagy más, nem attribútumalapú változások mentés előtti kontextusa.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function captureUpdateAuditContext(Model $model, array $attributes): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    protected function updatedAuditProperties(Model $model, array $attributes, array $context): array
    {
        return [];
    }

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
