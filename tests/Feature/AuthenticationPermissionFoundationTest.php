<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use App\Policies\EmployeePolicy;
use App\Policies\UserPolicy;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthenticationPermissionFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_works(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login', [
            'email' => 'login@example.com',
            'password' => 'password',
            'remember' => true,
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_logout_works(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_user_can_be_connected_to_employee_record(): void
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertTrue($user->employee->is($employee));
        $this->assertTrue($employee->user->is($user));
    }

    public function test_roles_and_permissions_seeder_is_idempotent(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertDatabaseCount('roles', 7);
        $this->assertDatabaseCount('permissions', 103);
    }

    public function test_super_admin_gets_all_permissions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $role = Role::query()->where('name', 'super-admin')->firstOrFail();

        $this->assertSame(Permission::query()->count(), $role->permissions()->count());
    }

    public function test_auth_user_is_available_in_inertia_shared_data(): void
    {
        $user = User::factory()->create([
            'email' => 'shared@example.com',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Dashboard')
                ->where('auth.user.email', 'shared@example.com')
                ->has('auth.permissions')
                ->has('auth.roles'));
    }

    public function test_permission_middleware_works(): void
    {
        Route::get('/permission-check-test', fn (): string => 'ok')
            ->middleware(['web', 'auth', 'permission:users.view']);

        Permission::query()->create([
            'name' => 'users.view',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)->get('/permission-check-test')->assertForbidden();

        $user->givePermissionTo('users.view');

        $this->actingAs($user)->get('/permission-check-test')->assertOk();
    }

    public function test_user_policy_is_loaded(): void
    {
        $this->assertInstanceOf(UserPolicy::class, Gate::getPolicyFor(User::class));
    }

    public function test_employee_policy_is_loaded(): void
    {
        $this->assertInstanceOf(EmployeePolicy::class, Gate::getPolicyFor(Employee::class));
    }

    public function test_login_event_is_written_to_activity_log(): void
    {
        $user = User::factory()->create([
            'email' => 'audit-login@example.com',
            'email_verified_at' => now(),
        ]);

        $this->post('/login', [
            'email' => 'audit-login@example.com',
            'password' => 'password',
        ]);

        $activity = Activity::query()
            ->where('event', 'user_logged_in')
            ->firstOrFail();

        $this->assertTrue($activity->subject->is($user));
        $this->assertTrue($activity->causer->is($user));
    }
}
