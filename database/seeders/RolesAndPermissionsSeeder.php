<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Seed application authorization roles and permissions.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'permissions.view',
            'employees.view',
            'employees.create',
            'employees.update',
            'employees.delete',
            'factory-units.view',
            'factory-units.create',
            'factory-units.update',
            'factory-units.delete',
            'locations.view',
            'locations.create',
            'locations.update',
            'locations.delete',
            'professional-roles.view',
            'professional-roles.create',
            'professional-roles.update',
            'professional-roles.delete',
            'items.view',
            'items.create',
            'items.update',
            'items.delete',
            'boms.view',
            'boms.create',
            'boms.update',
            'boms.delete',
            'operation-types.view',
            'operation-types.create',
            'operation-types.update',
            'operation-types.delete',
            'operation-sequences.view',
            'operation-sequences.create',
            'operation-sequences.update',
            'operation-sequences.delete',
            'customers.view',
            'customers.create',
            'customers.update',
            'customers.delete',
            'suppliers.view',
            'suppliers.create',
            'suppliers.update',
            'suppliers.delete',
            'customer-orders.view',
            'customer-orders.create',
            'customer-orders.update',
            'customer-orders.delete',
            'customer-orders.confirm',
            'customer-orders.cancel',
            'production-plans.view',
            'production-plans.create',
            'production-plans.update',
            'production-plans.delete',
            'production-plans.approve',
            'production-orders.generate',
            'production.view',
            'production.create',
            'production.update',
            'production.delete',
            'production.execute',
            'production.check',
            'inventory.view',
            'inventory.create',
            'inventory.update',
            'inventory.delete',
            'inventory.reserve',
            'inventory.release',
            'inventory.adjust',
            'procurement.view',
            'procurement.create',
            'procurement.update',
            'procurement.delete',
            'documents.view',
            'documents.create',
            'documents.update',
            'documents.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        foreach ($this->rolePermissions() as $roleName => $rolePermissions) {
            $role = Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($roleName === 'super-admin' ? $permissions : $rolePermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function rolePermissions(): array
    {
        return [
            'super-admin' => [],
            'production-manager' => [
                'employees.view',
                'factory-units.view',
                'locations.view',
                'professional-roles.view',
                'items.view',
                'items.create',
                'items.update',
                'items.delete',
                'boms.view',
                'boms.create',
                'boms.update',
                'boms.delete',
                'operation-types.view',
                'operation-types.create',
                'operation-types.update',
                'operation-types.delete',
                'operation-sequences.view',
                'operation-sequences.create',
                'operation-sequences.update',
                'operation-sequences.delete',
                'customers.view',
                'customer-orders.view',
                'customer-orders.create',
                'customer-orders.update',
                'customer-orders.delete',
                'customer-orders.confirm',
                'customer-orders.cancel',
                'production-plans.view',
                'production-plans.create',
                'production-plans.update',
                'production-plans.delete',
                'production-plans.approve',
                'production-orders.generate',
                'production.view',
                'production.create',
                'production.update',
                'production.execute',
                'production.check',
                'inventory.view',
                'inventory.reserve',
                'documents.view',
                'documents.create',
                'documents.update',
            ],
            'warehouse-manager' => [
                'inventory.view',
                'inventory.create',
                'inventory.update',
                'inventory.delete',
                'inventory.reserve',
                'inventory.release',
                'inventory.adjust',
                'production.view',
                'procurement.view',
                'suppliers.view',
                'suppliers.create',
                'suppliers.update',
                'suppliers.delete',
                'documents.view',
                'documents.create',
            ],
            'procurement-manager' => [
                'procurement.view',
                'procurement.create',
                'procurement.update',
                'procurement.delete',
                'suppliers.view',
                'suppliers.create',
                'suppliers.update',
                'suppliers.delete',
                'inventory.view',
                'documents.view',
                'documents.create',
                'documents.update',
            ],
            'quality-manager' => [
                'production.view',
                'production.check',
                'documents.view',
                'documents.create',
                'documents.update',
            ],
            'worker' => [
                'production.view',
                'production.execute',
                'documents.view',
            ],
            'viewer' => [
                'users.view',
                'employees.view',
                'factory-units.view',
                'locations.view',
                'professional-roles.view',
                'items.view',
                'boms.view',
                'operation-types.view',
                'operation-sequences.view',
                'customers.view',
                'suppliers.view',
                'customer-orders.view',
                'production-plans.view',
                'production.view',
                'inventory.view',
                'procurement.view',
                'documents.view',
            ],
        ];
    }
}
