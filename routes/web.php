<?php

use App\Http\Controllers\Admin\BomController as AdminBomController;
use App\Http\Controllers\Admin\CapacityController as AdminCapacityController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\CustomerOrderController as AdminCustomerOrderController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DocumentController as AdminDocumentController;
use App\Http\Controllers\Admin\EmployeeController as AdminEmployeeController;
use App\Http\Controllers\Admin\FactoryUnitController as AdminFactoryUnitController;
use App\Http\Controllers\Admin\GoodsReceiptController as AdminGoodsReceiptController;
use App\Http\Controllers\Admin\Inventory\MaterialRequirementController as AdminMaterialRequirementController;
use App\Http\Controllers\Admin\Inventory\ShortageController as AdminShortageController;
use App\Http\Controllers\Admin\Inventory\StockBalanceController as AdminStockBalanceController;
use App\Http\Controllers\Admin\Inventory\StockMovementController as AdminStockMovementController;
use App\Http\Controllers\Admin\Inventory\StockReservationController as AdminStockReservationController;
use App\Http\Controllers\Admin\ItemController as AdminItemController;
use App\Http\Controllers\Admin\LocationController as AdminLocationController;
use App\Http\Controllers\Admin\ManufacturingIntelligenceController as AdminManufacturingIntelligenceController;
use App\Http\Controllers\Admin\OperationSequenceController as AdminOperationSequenceController;
use App\Http\Controllers\Admin\OperationTypeController as AdminOperationTypeController;
use App\Http\Controllers\Admin\PermissionController as AdminPermissionController;
use App\Http\Controllers\Admin\ProcurementDashboardController as AdminProcurementDashboardController;
use App\Http\Controllers\Admin\ProductionPlanController as AdminProductionPlanController;
use App\Http\Controllers\Admin\ProductionTaskController as AdminProductionTaskController;
use App\Http\Controllers\Admin\ProductionTaskMaterialController as AdminProductionTaskMaterialController;
use App\Http\Controllers\Admin\ProfessionalRoleController as AdminProfessionalRoleController;
use App\Http\Controllers\Admin\PurchaseOrderController as AdminPurchaseOrderController;
use App\Http\Controllers\Admin\PurchaseRequisitionController as AdminPurchaseRequisitionController;
use App\Http\Controllers\Admin\QualityCheckController as AdminQualityCheckController;
use App\Http\Controllers\Admin\ReportsController as AdminReportsController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\ShopFloorController as AdminShopFloorController;
use App\Http\Controllers\Admin\SupplierController as AdminSupplierController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\PreferenceController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function (): void {
    Route::post('/preferences/locale', [PreferenceController::class, 'setLocale'])->name('preferences.locale');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/password', [ProfileController::class, 'password'])->name('profile.password');
});

Route::middleware(['auth', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('dashboard', AdminDashboardController::class)->name('dashboard');
        Route::prefix('capacity')
            ->name('capacity.')
            ->group(function (): void {
                Route::get('dashboard', [AdminCapacityController::class, 'dashboard'])->name('dashboard');
                Route::get('factory-units', [AdminCapacityController::class, 'factoryUnits'])->name('factory-units');
                Route::get('employees', [AdminCapacityController::class, 'employees'])->name('employees');
                Route::get('schedule', [AdminCapacityController::class, 'schedule'])->name('schedule');
                Route::post('schedule', [AdminCapacityController::class, 'storeSchedule'])->name('schedule.store');
                Route::get('simulate', [AdminCapacityController::class, 'simulate'])->name('simulate');
                Route::post('simulate', [AdminCapacityController::class, 'runSimulation'])->name('simulate.run');
            });
        Route::prefix('reports')
            ->name('reports.')
            ->group(function (): void {
                Route::get('customer-orders', [AdminReportsController::class, 'customerOrders'])->name('customer-orders');
                Route::get('production', [AdminReportsController::class, 'production'])->name('production');
                Route::get('inventory', [AdminReportsController::class, 'inventory'])->name('inventory');
                Route::get('procurement', [AdminReportsController::class, 'procurement'])->name('procurement');
                Route::get('quality', [AdminReportsController::class, 'quality'])->name('quality');
                Route::get('shop-floor', [AdminReportsController::class, 'shopFloor'])->name('shop-floor');
            });
        Route::prefix('intelligence')
            ->name('intelligence.')
            ->group(function (): void {
                Route::get('dashboard', [AdminManufacturingIntelligenceController::class, 'dashboard'])->name('dashboard');
                Route::get('bottlenecks', [AdminManufacturingIntelligenceController::class, 'bottlenecks'])->name('bottlenecks');
                Route::get('material-forecast', [AdminManufacturingIntelligenceController::class, 'materialForecast'])->name('material-forecast');
                Route::get('supplier-performance', [AdminManufacturingIntelligenceController::class, 'supplierPerformance'])->name('supplier-performance');
                Route::get('quality-trends', [AdminManufacturingIntelligenceController::class, 'qualityTrends'])->name('quality-trends');
                Route::get('risks', [AdminManufacturingIntelligenceController::class, 'risks'])->name('risks');
                Route::get('recommendations', [AdminManufacturingIntelligenceController::class, 'recommendations'])->name('recommendations');
            });
        Route::resource('users', AdminUserController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('roles', AdminRoleController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('permissions', AdminPermissionController::class)->only(['index']);
        Route::resource('employees', AdminEmployeeController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('factory-units', AdminFactoryUnitController::class)
            ->parameters(['factory-units' => 'factoryUnit'])
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('locations', AdminLocationController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('professional-roles', AdminProfessionalRoleController::class)
            ->parameters(['professional-roles' => 'professionalRole'])
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('items', AdminItemController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('boms', AdminBomController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('operation-types', AdminOperationTypeController::class)
            ->parameters(['operation-types' => 'operationType'])
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('operation-sequences', AdminOperationSequenceController::class)
            ->parameters(['operation-sequences' => 'operationSequence'])
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('customers', AdminCustomerController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('suppliers', AdminSupplierController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::patch('customer-orders/{customerOrder}/confirm', [AdminCustomerOrderController::class, 'confirm'])
            ->name('customer-orders.confirm');
        Route::patch('customer-orders/{customerOrder}/cancel', [AdminCustomerOrderController::class, 'cancel'])
            ->name('customer-orders.cancel');
        Route::resource('customer-orders', AdminCustomerOrderController::class)
            ->parameters(['customer-orders' => 'customerOrder'])
            ->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::patch('production-plans/{productionPlan}/approve', [AdminProductionPlanController::class, 'approve'])
            ->name('production-plans.approve');
        Route::post('production-plans/{productionPlan}/generate-production-orders', [AdminProductionPlanController::class, 'generateProductionOrders'])
            ->name('production-plans.generate-production-orders');
        Route::resource('production-plans', AdminProductionPlanController::class)
            ->parameters(['production-plans' => 'productionPlan'])
            ->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::get('shop-floor', [AdminShopFloorController::class, 'index'])->name('shop-floor.index');
        Route::get('shop-floor/my-tasks', [AdminShopFloorController::class, 'myTasks'])->name('shop-floor.my-tasks');
        Route::post('production-tasks/generate-from-order', [AdminProductionTaskController::class, 'generateFromOrder'])
            ->name('production-tasks.generate-from-order');
        Route::patch('production-tasks/{productionTask}/start', [AdminProductionTaskController::class, 'start'])
            ->name('production-tasks.start');
        Route::patch('production-tasks/{productionTask}/finish', [AdminProductionTaskController::class, 'finish'])
            ->name('production-tasks.finish');
        Route::post('production-tasks/{productionTask}/materials', [AdminProductionTaskMaterialController::class, 'store'])
            ->name('production-tasks.materials.store');
        Route::post('production-tasks/{productionTask}/quality-checks', [AdminQualityCheckController::class, 'store'])
            ->name('production-tasks.quality-checks.store');
        Route::resource('production-tasks', AdminProductionTaskController::class)
            ->parameters(['production-tasks' => 'productionTask'])
            ->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::prefix('inventory')
            ->name('inventory.')
            ->group(function (): void {
                Route::get('stock-balances', [AdminStockBalanceController::class, 'index'])->name('stock-balances.index');
                Route::get('stock-movements', [AdminStockMovementController::class, 'index'])->name('stock-movements.index');
                Route::patch('stock-reservations/{stockReservation}/release', [AdminStockReservationController::class, 'release'])
                    ->name('stock-reservations.release');
                Route::get('stock-reservations', [AdminStockReservationController::class, 'index'])->name('stock-reservations.index');
                Route::get('material-requirements', [AdminMaterialRequirementController::class, 'index'])->name('material-requirements.index');
                Route::get('shortages', [AdminShortageController::class, 'index'])->name('shortages.index');
            });
        Route::get('procurement/dashboard', AdminProcurementDashboardController::class)->name('procurement.dashboard');
        Route::post('purchase-requisitions/generate-from-material-requirements', [AdminPurchaseRequisitionController::class, 'generateFromMaterialRequirements'])
            ->name('purchase-requisitions.generate-from-material-requirements');
        Route::patch('purchase-requisitions/{purchaseRequisition}/approve', [AdminPurchaseRequisitionController::class, 'approve'])
            ->name('purchase-requisitions.approve');
        Route::post('purchase-requisitions/{purchaseRequisition}/generate-purchase-order', [AdminPurchaseRequisitionController::class, 'generatePurchaseOrder'])
            ->name('purchase-requisitions.generate-purchase-order');
        Route::resource('purchase-requisitions', AdminPurchaseRequisitionController::class)
            ->parameters(['purchase-requisitions' => 'purchaseRequisition'])
            ->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::patch('purchase-orders/{purchaseOrder}/approve', [AdminPurchaseOrderController::class, 'approve'])
            ->name('purchase-orders.approve');
        Route::patch('purchase-orders/{purchaseOrder}/close', [AdminPurchaseOrderController::class, 'close'])
            ->name('purchase-orders.close');
        Route::resource('purchase-orders', AdminPurchaseOrderController::class)
            ->parameters(['purchase-orders' => 'purchaseOrder'])
            ->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::post('goods-receipts/{goodsReceipt}/post', [AdminGoodsReceiptController::class, 'post'])
            ->name('goods-receipts.post');
        Route::resource('goods-receipts', AdminGoodsReceiptController::class)
            ->parameters(['goods-receipts' => 'goodsReceipt'])
            ->only(['index', 'show', 'store']);
        Route::get('documents/{document}/download', [AdminDocumentController::class, 'download'])
            ->name('documents.download');
        Route::patch('documents/{document}/approve', [AdminDocumentController::class, 'approve'])
            ->name('documents.approve');
        Route::patch('documents/{document}/make-current', [AdminDocumentController::class, 'makeCurrent'])
            ->name('documents.make-current');
        Route::resource('documents', AdminDocumentController::class)
            ->only(['index', 'show', 'store', 'update', 'destroy']);
    });

require __DIR__.'/auth.php';
