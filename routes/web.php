<?php

use App\Http\Controllers\Admin\EmployeeController as AdminEmployeeController;
use App\Http\Controllers\Admin\FactoryUnitController as AdminFactoryUnitController;
use App\Http\Controllers\Admin\BomController as AdminBomController;
use App\Http\Controllers\Admin\CustomerOrderController as AdminCustomerOrderController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\ItemController as AdminItemController;
use App\Http\Controllers\Admin\LocationController as AdminLocationController;
use App\Http\Controllers\Admin\OperationSequenceController as AdminOperationSequenceController;
use App\Http\Controllers\Admin\OperationTypeController as AdminOperationTypeController;
use App\Http\Controllers\Admin\PermissionController as AdminPermissionController;
use App\Http\Controllers\Admin\ProfessionalRoleController as AdminProfessionalRoleController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\SupplierController as AdminSupplierController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
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
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/password', [ProfileController::class, 'password'])->name('profile.password');
});

Route::middleware(['auth', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
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
    });

require __DIR__.'/auth.php';
