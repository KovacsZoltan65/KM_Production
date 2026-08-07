<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
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
            'item-suppliers.view',
            'item-suppliers.create',
            'item-suppliers.update',
            'item-suppliers.delete',
            'supply-proposals.view',
            'supply-proposals.create',
            'supply-proposals.update',
            'supply-proposals.approve',
            'supply-proposals.delete',
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
            'production-tasks.view',
            'production-tasks.create',
            'production-tasks.update',
            'production-tasks.delete',
            'production-tasks.start',
            'production-tasks.finish',
            'production-tasks.materials',
            'production-tasks.check',
            'shop-floor.view',
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
            'procurement.approve',
            'purchase-orders.generate',
            'goods-receipts.create',
            'goods-receipts.post',
            'dashboard.view',
            'reports.view',
            'intelligence.view',
            'intelligence.recommendations',
            'capacity.view',
            'capacity.plan',
            'capacity.override',
            'documents.view',
            'documents.create',
            'documents.update',
            'documents.delete',
            'documents.download',
            'documents.approve',
            'documents.version',
        ];

        $rolePermissions = $this->rolePermissions();

        if ($this->authorizationDataIsCurrent($permissions, $rolePermissions)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return;
        }

        $timestamp = now();

        Permission::query()->upsert(
            array_map(fn (string $permission): array => [
                'name' => $permission,
                'guard_name' => 'web',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ], $permissions),
            ['name', 'guard_name'],
            ['updated_at'],
        );

        Role::query()->upsert(
            array_map(fn (string $roleName): array => [
                'name' => $roleName,
                'guard_name' => 'web',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ], array_keys($rolePermissions)),
            ['name', 'guard_name'],
            ['updated_at'],
        );

        /** @var Collection<string, Role> $roles */
        $roles = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', array_keys($rolePermissions))
            ->get()
            ->keyBy('name');

        foreach ($rolePermissions as $roleName => $permissionsForRole) {
            $role = $roles->get($roleName);

            $role?->syncPermissions($roleName === 'super-admin' ? $permissions : $permissionsForRole);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param  array<int, string>  $permissions
     * @param  array<string, array<int, string>>  $rolePermissions
     */
    private function authorizationDataIsCurrent(array $permissions, array $rolePermissions): bool
    {
        $storedPermissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $permissions)
            ->pluck('name')
            ->all();

        if (count($storedPermissions) !== count($permissions)) {
            return false;
        }

        $roles = Role::query()
            ->with('permissions:id,name,guard_name')
            ->where('guard_name', 'web')
            ->whereIn('name', array_keys($rolePermissions))
            ->get()
            ->keyBy('name');

        if ($roles->count() !== count($rolePermissions)) {
            return false;
        }

        foreach ($rolePermissions as $roleName => $permissionsForRole) {
            $expected = $roleName === 'super-admin' ? $permissions : $permissionsForRole;
            sort($expected);

            $actual = $roles->get($roleName)?->permissions
                ->pluck('name')
                ->sort()
                ->values()
                ->all();

            if ($actual !== $expected) {
                return false;
            }
        }

        return true;
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
                'item-suppliers.view',
                'supply-proposals.view',
                'supply-proposals.create',
                'supply-proposals.update',
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
                'production-tasks.view',
                'production-tasks.create',
                'production-tasks.update',
                'production-tasks.delete',
                'production-tasks.start',
                'production-tasks.finish',
                'production-tasks.materials',
                'production-tasks.check',
                'shop-floor.view',
                'inventory.view',
                'inventory.reserve',
                'dashboard.view',
                'reports.view',
                'intelligence.view',
                'intelligence.recommendations',
                'capacity.view',
                'capacity.plan',
                'documents.view',
                'documents.create',
                'documents.update',
                'documents.delete',
                'documents.download',
                'documents.approve',
                'documents.version',
            ],
            'warehouse-manager' => [
                'inventory.view',
                'inventory.create',
                'inventory.update',
                'inventory.delete',
                'inventory.reserve',
                'inventory.release',
                'inventory.adjust',
                'item-suppliers.view',
                'supply-proposals.view',
                'production.view',
                'procurement.view',
                'suppliers.view',
                'suppliers.create',
                'suppliers.update',
                'suppliers.delete',
                'dashboard.view',
                'reports.view',
                'intelligence.view',
                'intelligence.recommendations',
                'documents.view',
                'documents.create',
                'documents.download',
            ],
            'procurement-manager' => [
                'item-suppliers.view',
                'item-suppliers.create',
                'item-suppliers.update',
                'item-suppliers.delete',
                'supply-proposals.view',
                'supply-proposals.create',
                'supply-proposals.update',
                'supply-proposals.approve',
                'supply-proposals.delete',
                'procurement.view',
                'procurement.create',
                'procurement.update',
                'procurement.delete',
                'procurement.approve',
                'purchase-orders.generate',
                'goods-receipts.create',
                'goods-receipts.post',
                'suppliers.view',
                'suppliers.create',
                'suppliers.update',
                'suppliers.delete',
                'inventory.view',
                'dashboard.view',
                'reports.view',
                'intelligence.view',
                'intelligence.recommendations',
                'documents.view',
                'documents.create',
                'documents.update',
                'documents.download',
            ],
            'quality-manager' => [
                'production.view',
                'production.check',
                'production-tasks.view',
                'production-tasks.check',
                'shop-floor.view',
                'dashboard.view',
                'reports.view',
                'intelligence.view',
                'documents.view',
                'documents.download',
                'documents.approve',
            ],
            'worker' => [
                'production.view',
                'production.execute',
                'production-tasks.view',
                'production-tasks.start',
                'production-tasks.finish',
                'production-tasks.materials',
                'shop-floor.view',
                'documents.view',
                'documents.download',
            ],
            'viewer' => [
                'users.view',
                'employees.view',
                'factory-units.view',
                'locations.view',
                'professional-roles.view',
                'items.view',
                'item-suppliers.view',
                'supply-proposals.view',
                'boms.view',
                'operation-types.view',
                'operation-sequences.view',
                'customers.view',
                'suppliers.view',
                'customer-orders.view',
                'production-plans.view',
                'production.view',
                'production-tasks.view',
                'inventory.view',
                'procurement.view',
                'dashboard.view',
                'reports.view',
                'intelligence.view',
                'capacity.view',
                'documents.view',
            ],
        ];
    }
}
