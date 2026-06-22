<?php

use App\Http\Controllers\Admin\EmployeeController as AdminEmployeeController;
use App\Http\Controllers\Admin\FactoryUnitController as AdminFactoryUnitController;
use App\Http\Controllers\Admin\LocationController as AdminLocationController;
use App\Http\Controllers\Admin\PermissionController as AdminPermissionController;
use App\Http\Controllers\Admin\ProfessionalRoleController as AdminProfessionalRoleController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
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
    });

require __DIR__.'/auth.php';
