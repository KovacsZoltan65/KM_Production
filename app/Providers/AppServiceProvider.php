<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use App\Repositories\Admin\BomRepository;
use App\Repositories\Admin\CustomerOrderRepository;
use App\Repositories\Admin\CustomerAdminRepository;
use App\Repositories\Admin\EmployeeRepository;
use App\Repositories\Admin\FactoryUnitRepository;
use App\Repositories\Admin\ItemRepository;
use App\Repositories\Admin\LocationRepository;
use App\Repositories\Admin\OperationSequenceRepository;
use App\Repositories\Admin\OperationTypeRepository;
use App\Repositories\Admin\PermissionRepository;
use App\Repositories\Admin\ProfessionalRoleRepository;
use App\Repositories\Admin\ProductionPlanRepository;
use App\Repositories\Admin\RoleRepository;
use App\Repositories\Admin\SupplierAdminRepository;
use App\Repositories\Admin\UserRepository;
use App\Repositories\Contracts\BomRepositoryInterface;
use App\Repositories\Contracts\CustomerOrderRepositoryInterface;
use App\Repositories\Contracts\CustomerAdminRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\FactoryUnitRepositoryInterface;
use App\Repositories\Contracts\ItemRepositoryInterface;
use App\Repositories\Contracts\LocationRepositoryInterface;
use App\Repositories\Contracts\OperationSequenceRepositoryInterface;
use App\Repositories\Contracts\OperationTypeRepositoryInterface;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use App\Repositories\Contracts\ProfessionalRoleRepositoryInterface;
use App\Repositories\Contracts\ProductionPlanRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\SupplierAdminRepositoryInterface;
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
        $this->app->bind(ItemRepositoryInterface::class, ItemRepository::class);
        $this->app->bind(BomRepositoryInterface::class, BomRepository::class);
        $this->app->bind(OperationTypeRepositoryInterface::class, OperationTypeRepository::class);
        $this->app->bind(OperationSequenceRepositoryInterface::class, OperationSequenceRepository::class);
        $this->app->bind(CustomerAdminRepositoryInterface::class, CustomerAdminRepository::class);
        $this->app->bind(SupplierAdminRepositoryInterface::class, SupplierAdminRepository::class);
        $this->app->bind(CustomerOrderRepositoryInterface::class, CustomerOrderRepository::class);
        $this->app->bind(ProductionPlanRepositoryInterface::class, ProductionPlanRepository::class);
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
