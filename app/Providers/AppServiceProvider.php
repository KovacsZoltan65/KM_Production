<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use App\Repositories\Admin\EmployeeRepository;
use App\Repositories\Admin\FactoryUnitRepository;
use App\Repositories\Admin\LocationRepository;
use App\Repositories\Admin\PermissionRepository;
use App\Repositories\Admin\ProfessionalRoleRepository;
use App\Repositories\Admin\RoleRepository;
use App\Repositories\Admin\UserRepository;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\FactoryUnitRepositoryInterface;
use App\Repositories\Contracts\LocationRepositoryInterface;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use App\Repositories\Contracts\ProfessionalRoleRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(RoleRepositoryInterface::class, RoleRepository::class);
        $this->app->bind(PermissionRepositoryInterface::class, PermissionRepository::class);
        $this->app->bind(EmployeeRepositoryInterface::class, EmployeeRepository::class);
        $this->app->bind(FactoryUnitRepositoryInterface::class, FactoryUnitRepository::class);
        $this->app->bind(LocationRepositoryInterface::class, LocationRepository::class);
        $this->app->bind(ProfessionalRoleRepositoryInterface::class, ProfessionalRoleRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);

        Gate::before(function (User $user): ?bool {
            return $user->hasRole('super-admin') ? true : null;
        });
    }
}
