<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Repositories\Contracts\AdminRepositoryInterface;
use App\Services\AuditLogService;
use App\Services\CodeCreationService;
use App\Support\CodeGeneration\CodeCreationResult;
use Illuminate\Database\Eloquent\Model;

/**
 * A kódérzékeny törzsadatok közös létrehozási és ütközéskezelési folyamata.
 */
abstract class CodeAwareAdminService extends AbstractAdminService
{
    private ?CodeCreationResult $lastCreation = null;

    public function __construct(
        AdminRepositoryInterface $repository,
        AuditLogService $auditLogService,
        private readonly CodeCreationService $codeCreationService,
    ) {
        parent::__construct($repository, $auditLogService);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes, ?User $causer = null): Model
    {
        $attributes = $this->normalizeAttributes($attributes);
        $this->lastCreation = $this->codeCreationService->create(
            $this->codeType($attributes),
            $attributes,
            $this->repository,
        );
        $model = $this->lastCreation->model;

        $this->auditLogService->log($this->createdEvent(), $model, [], $causer);
        $this->afterWrite();

        return $model;
    }

    public function creationMessage(): string
    {
        if ($this->lastCreation?->codeWasReplaced()) {
            return __('code_generation.messages.generated_collision_replaced', [
                'original' => $this->lastCreation->originalCode,
                'actual' => $this->lastCreation->actualCode,
            ]);
        }

        return __('messages.created');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    abstract protected function codeType(array $attributes): string;
}
