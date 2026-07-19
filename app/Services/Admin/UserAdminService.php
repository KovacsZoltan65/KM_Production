<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class UserAdminService extends AbstractAdminService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        AuditLogService $auditLogService,
    ) {
        parent::__construct($this->users, $auditLogService);
    }

    /** @return LengthAwarePaginator<int, User> */
    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->users->paginateForAdminIndex($filters, $perPage);
    }

    public function delete(Model $model, ?User $causer = null): void
    {
        /** @var User $model */
        if ($causer?->is($model)) {
            throw ValidationException::withMessages([
                'user' => __('master_data.users.validation.cannot_delete_self'),
            ]);
        }

        if ($model->hasRole('super-admin') && User::role('super-admin')->count() <= 1) {
            throw ValidationException::withMessages([
                'user' => __('master_data.users.validation.super_admin_required'),
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
