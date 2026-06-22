<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class UserAdminService extends AbstractAdminService
{
    public function __construct(UserRepositoryInterface $repository, AuditLogService $auditLogService)
    {
        parent::__construct($repository, $auditLogService);
    }

    public function delete(Model $model, ?User $causer = null): void
    {
        /** @var User $model */
        if ($causer?->is($model)) {
            throw ValidationException::withMessages([
                'user' => 'You cannot delete your own user account.',
            ]);
        }

        if ($model->hasRole('super-admin') && User::role('super-admin')->count() <= 1) {
            throw ValidationException::withMessages([
                'user' => 'At least one super-admin user must remain.',
            ]);
        }

        parent::delete($model, $causer);
    }

    protected function createdEvent(): string
    {
        return 'admin_user_created';
    }

    protected function updatedEvent(): string
    {
        return 'admin_user_updated';
    }

    protected function deletedEvent(): string
    {
        return 'admin_user_deleted';
    }
}
