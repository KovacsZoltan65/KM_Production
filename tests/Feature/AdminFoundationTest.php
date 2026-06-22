<?php

namespace Tests\Feature;

use App\Enums\LocationType;
use App\Models\Employee;
use App\Models\FactoryUnit;
use App\Models\Location;
use App\Models\ProfessionalRole;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_pages(): void
    {
        $this->get('/admin/users')->assertRedirect('/login');
    }

    public function test_super_admin_can_access_users_index(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk();
    }

    public function test_user_without_permission_cannot_access_users_index(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_user_can_be_created_with_role(): void
    {
        $admin = $this->superAdmin();
        Role::query()->firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);

        $this->actingAs($admin)
            ->post('/admin/users', [
                'name' => 'Created User',
                'email' => 'created@example.com',
                'password' => 'password',
                'roles' => ['viewer'],
            ])
            ->assertRedirect();

        $user = User::query()->where('email', 'created@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole('viewer'));
    }

    public function test_user_can_be_updated(): void
    {
        $admin = $this->superAdmin();
        $user = User::factory()->create(['name' => 'Old Name']);

        $this->actingAs($admin)
            ->put("/admin/users/{$user->id}", [
                'name' => 'New Name',
                'email' => $user->email,
                'password' => null,
                'roles' => [],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
        ]);
    }

    public function test_user_can_be_deleted_when_not_last_super_admin(): void
    {
        $admin = $this->superAdmin('admin@example.com');
        $target = $this->superAdmin('target@example.com');

        $this->actingAs($admin)
            ->delete("/admin/users/{$target->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_last_super_admin_deletion_is_forbidden(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $actor = User::factory()->create(['email_verified_at' => now()]);
        $actor->givePermissionTo('users.delete');
        $target = $this->superAdmin('only-super-admin@example.com');

        $this->actingAs($actor)
            ->delete("/admin/users/{$target->id}")
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_employee_crud_works(): void
    {
        $admin = $this->superAdmin();
        $professionalRole = ProfessionalRole::factory()->create();

        $this->actingAs($admin)
            ->post('/admin/employees', [
                'employee_number' => 'EMP-ADMIN-001',
                'name' => 'Admin Employee',
                'email' => 'employee@example.com',
                'phone' => '+36 1 111 1111',
                'professional_role_id' => $professionalRole->id,
                'user_id' => null,
                'is_active' => true,
                'hired_at' => '2026-01-01',
                'left_at' => null,
            ])
            ->assertRedirect();

        $employee = Employee::query()->where('employee_number', 'EMP-ADMIN-001')->firstOrFail();

        $this->actingAs($admin)
            ->put("/admin/employees/{$employee->id}", [
                'employee_number' => 'EMP-ADMIN-001',
                'name' => 'Updated Employee',
                'email' => 'employee@example.com',
                'phone' => '+36 1 222 2222',
                'professional_role_id' => $professionalRole->id,
                'user_id' => null,
                'is_active' => false,
                'hired_at' => '2026-01-01',
                'left_at' => null,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'name' => 'Updated Employee']);

        $this->actingAs($admin)->delete("/admin/employees/{$employee->id}")->assertRedirect();
        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    }

    public function test_factory_unit_crud_works(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->post('/admin/factory-units', [
                'code' => 'ADM',
                'name' => 'Admin Factory',
                'description' => 'Admin factory unit.',
                'daily_capacity_minutes' => 480,
                'shift_count' => 2,
                'is_active' => true,
            ])
            ->assertRedirect();

        $factoryUnit = FactoryUnit::query()->where('code', 'ADM')->firstOrFail();

        $this->actingAs($admin)
            ->put("/admin/factory-units/{$factoryUnit->id}", [
                'code' => 'ADM',
                'name' => 'Updated Factory',
                'description' => null,
                'daily_capacity_minutes' => 600,
                'shift_count' => 3,
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('factory_units', ['id' => $factoryUnit->id, 'name' => 'Updated Factory']);

        $this->actingAs($admin)->delete("/admin/factory-units/{$factoryUnit->id}")->assertRedirect();
        $this->assertSoftDeleted('factory_units', ['id' => $factoryUnit->id]);
    }

    public function test_location_crud_works(): void
    {
        $admin = $this->superAdmin();
        $factoryUnit = FactoryUnit::factory()->create();

        $this->actingAs($admin)
            ->post('/admin/locations', [
                'factory_unit_id' => $factoryUnit->id,
                'code' => 'ADM-WH',
                'name' => 'Admin Warehouse',
                'location_type' => LocationType::Warehouse->value,
                'description' => null,
                'is_active' => true,
            ])
            ->assertRedirect();

        $location = Location::query()->where('code', 'ADM-WH')->firstOrFail();

        $this->actingAs($admin)
            ->put("/admin/locations/{$location->id}", [
                'factory_unit_id' => $factoryUnit->id,
                'code' => 'ADM-WH',
                'name' => 'Updated Warehouse',
                'location_type' => LocationType::Workshop->value,
                'description' => null,
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('locations', ['id' => $location->id, 'name' => 'Updated Warehouse']);

        $this->actingAs($admin)->delete("/admin/locations/{$location->id}")->assertRedirect();
        $this->assertSoftDeleted('locations', ['id' => $location->id]);
    }

    public function test_professional_role_crud_works(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->post('/admin/professional-roles', [
                'code' => 'ADM-ROLE',
                'name' => 'Admin Role',
                'description' => null,
                'is_active' => true,
            ])
            ->assertRedirect();

        $professionalRole = ProfessionalRole::query()->where('code', 'ADM-ROLE')->firstOrFail();

        $this->actingAs($admin)
            ->put("/admin/professional-roles/{$professionalRole->id}", [
                'code' => 'ADM-ROLE',
                'name' => 'Updated Role',
                'description' => 'Updated.',
                'is_active' => false,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('professional_roles', ['id' => $professionalRole->id, 'name' => 'Updated Role']);

        $this->actingAs($admin)->delete("/admin/professional-roles/{$professionalRole->id}")->assertRedirect();
        $this->assertSoftDeleted('professional_roles', ['id' => $professionalRole->id]);
    }

    public function test_activity_log_is_created_for_admin_modification(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->post('/admin/factory-units', [
                'code' => 'LOG',
                'name' => 'Logged Factory',
                'description' => null,
                'daily_capacity_minutes' => 480,
                'shift_count' => 1,
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertTrue(Activity::query()->where('event', 'admin_factory_unit_created')->exists());
    }

    public function test_server_side_search_works_on_users_index(): void
    {
        $admin = $this->superAdmin();
        User::factory()->create(['name' => 'Search Target', 'email' => 'target@example.com']);
        User::factory()->create(['name' => 'Another User', 'email' => 'another@example.com']);

        $response = $this->actingAs($admin)->get('/admin/users?search=target');

        $response->assertOk();
        $response->assertSee('target@example.com');
        $response->assertDontSee('another@example.com');
    }

    private function superAdmin(string $email = 'admin@example.com'): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create([
            'email' => $email,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('super-admin');

        return $user;
    }
}
