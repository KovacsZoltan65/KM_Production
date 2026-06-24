<?php

namespace App\Providers;

use App\Models\User;
use App\Models\GoodsReceipt;
use App\Models\MaterialRequirement;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\StockReservation;
use App\Policies\GoodsReceiptPolicy;
use App\Policies\MaterialRequirementPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\PurchaseOrderPolicy;
use App\Policies\PurchaseRequisitionPolicy;
use App\Policies\RolePolicy;
use App\Policies\StockBalancePolicy;
use App\Policies\StockMovementPolicy;
use App\Policies\StockReservationPolicy;
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
use App\Repositories\Admin\GoodsReceiptRepository;
use App\Repositories\Admin\PurchaseOrderRepository;
use App\Repositories\Admin\PurchaseRequisitionRepository;
use App\Repositories\Admin\RoleRepository;
use App\Repositories\Admin\MaterialRequirementRepository;
use App\Repositories\Admin\StockBalanceRepository;
use App\Repositories\Admin\StockMovementRepository;
use App\Repositories\Admin\StockReservationRepository;
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
use App\Repositories\Contracts\GoodsReceiptRepositoryInterface;
use App\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use App\Repositories\Contracts\PurchaseRequisitionRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\MaterialRequirementRepositoryInterface;
use App\Repositories\Contracts\StockBalanceRepositoryInterface;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use App\Repositories\Contracts\StockReservationRepositoryInterface;
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
        $this->app->bind(StockBalanceRepositoryInterface::class, StockBalanceRepository::class);
        $this->app->bind(StockMovementRepositoryInterface::class, StockMovementRepository::class);
        $this->app->bind(StockReservationRepositoryInterface::class, StockReservationRepository::class);
        $this->app->bind(MaterialRequirementRepositoryInterface::class, MaterialRequirementRepository::class);
        $this->app->bind(PurchaseRequisitionRepositoryInterface::class, PurchaseRequisitionRepository::class);
        $this->app->bind(PurchaseOrderRepositoryInterface::class, PurchaseOrderRepository::class);
        $this->app->bind(GoodsReceiptRepositoryInterface::class, GoodsReceiptRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
        Gate::policy(StockBalance::class, StockBalancePolicy::class);
        Gate::policy(StockMovement::class, StockMovementPolicy::class);
        Gate::policy(StockReservation::class, StockReservationPolicy::class);
        Gate::policy(MaterialRequirement::class, MaterialRequirementPolicy::class);
        Gate::policy(PurchaseRequisition::class, PurchaseRequisitionPolicy::class);
        Gate::policy(PurchaseOrder::class, PurchaseOrderPolicy::class);
        Gate::policy(GoodsReceipt::class, GoodsReceiptPolicy::class);

        Gate::before(function (User $user): ?bool {
            return $user->hasRole('super-admin') ? true : null;
        });
    }
}
