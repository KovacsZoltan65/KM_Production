<?php

namespace Tests\Feature;

use App\Models\FactoryUnit;
use App\Models\ProfessionalRole;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RouteParameterAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RecordingFactoryUnitPolicy::$received = null;
        RecordingProfessionalRolePolicy::$received = null;
    }

    public function test_factory_unit_can_be_updated_with_permission(): void
    {
        $user = $this->userWithPermission('factory-units.update');
        $factoryUnit = FactoryUnit::factory()->create(['code' => 'FU-OLD']);

        $this->actingAs($user)
            ->put(route('admin.factory-units.update', $factoryUnit), $this->factoryUnitPayload('FU-NEW', 'Updated Factory'))
            ->assertRedirect();

        $this->assertDatabaseHas('factory_units', [
            'id' => $factoryUnit->id,
            'code' => 'FU-NEW',
            'name' => 'Updated Factory',
        ]);
    }

    public function test_factory_unit_update_is_forbidden_without_permission(): void
    {
        $user = $this->userWithoutPermissions();
        $factoryUnit = FactoryUnit::factory()->create(['name' => 'Original Factory']);

        $this->actingAs($user)
            ->put(route('admin.factory-units.update', $factoryUnit), $this->factoryUnitPayload($factoryUnit->code, 'Forbidden Update'))
            ->assertForbidden();

        $this->assertDatabaseHas('factory_units', [
            'id' => $factoryUnit->id,
            'name' => 'Original Factory',
        ]);
    }

    public function test_factory_unit_unchanged_code_is_ignored_by_unique_validation(): void
    {
        $user = $this->userWithPermission('factory-units.update');
        $factoryUnit = FactoryUnit::factory()->create(['code' => 'FU-SAME']);

        $this->actingAs($user)
            ->put(route('admin.factory-units.update', $factoryUnit), $this->factoryUnitPayload('FU-SAME', 'Same Code Factory'))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('factory_units', [
            'id' => $factoryUnit->id,
            'code' => 'FU-SAME',
            'name' => 'Same Code Factory',
        ]);
    }

    public function test_factory_unit_code_used_by_another_record_is_rejected(): void
    {
        $user = $this->userWithPermission('factory-units.update');
        $factoryUnit = FactoryUnit::factory()->create(['code' => 'FU-TARGET', 'name' => 'Target Factory']);
        FactoryUnit::factory()->create(['code' => 'FU-TAKEN']);

        $this->actingAs($user)
            ->from(route('admin.factory-units.index'))
            ->put(route('admin.factory-units.update', $factoryUnit), $this->factoryUnitPayload('FU-TAKEN', 'Invalid Update'))
            ->assertSessionHasErrors('code');

        $this->assertDatabaseHas('factory_units', [
            'id' => $factoryUnit->id,
            'code' => 'FU-TARGET',
            'name' => 'Target Factory',
        ]);
    }

    public function test_factory_unit_policy_receives_the_route_bound_model(): void
    {
        Gate::policy(FactoryUnit::class, RecordingFactoryUnitPolicy::class);
        $user = $this->userWithoutPermissions();
        $factoryUnit = FactoryUnit::factory()->create(['code' => 'FU-BOUND']);

        $this->actingAs($user)
            ->put(route('admin.factory-units.update', $factoryUnit), $this->factoryUnitPayload('FU-BOUND', 'Bound Factory'))
            ->assertRedirect();

        $this->assertTrue(RecordingFactoryUnitPolicy::$received?->is($factoryUnit) ?? false);
        $this->assertDatabaseHas('factory_units', ['id' => $factoryUnit->id, 'name' => 'Bound Factory']);
    }

    public function test_professional_role_can_be_updated_with_permission(): void
    {
        $user = $this->userWithPermission('professional-roles.update');
        $professionalRole = ProfessionalRole::factory()->create(['code' => 'ROLE-OLD']);

        $this->actingAs($user)
            ->put(route('admin.professional-roles.update', $professionalRole), $this->professionalRolePayload('ROLE-NEW', 'Updated Role'))
            ->assertRedirect();

        $this->assertDatabaseHas('professional_roles', [
            'id' => $professionalRole->id,
            'code' => 'ROLE-NEW',
            'name' => 'Updated Role',
        ]);
    }

    public function test_professional_role_update_is_forbidden_without_permission(): void
    {
        $user = $this->userWithoutPermissions();
        $professionalRole = ProfessionalRole::factory()->create(['name' => 'Original Role']);

        $this->actingAs($user)
            ->put(route('admin.professional-roles.update', $professionalRole), $this->professionalRolePayload($professionalRole->code, 'Forbidden Update'))
            ->assertForbidden();

        $this->assertDatabaseHas('professional_roles', [
            'id' => $professionalRole->id,
            'name' => 'Original Role',
        ]);
    }

    public function test_professional_role_unchanged_code_is_ignored_by_unique_validation(): void
    {
        $user = $this->userWithPermission('professional-roles.update');
        $professionalRole = ProfessionalRole::factory()->create(['code' => 'ROLE-SAME']);

        $this->actingAs($user)
            ->put(route('admin.professional-roles.update', $professionalRole), $this->professionalRolePayload('ROLE-SAME', 'Same Code Role'))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('professional_roles', [
            'id' => $professionalRole->id,
            'code' => 'ROLE-SAME',
            'name' => 'Same Code Role',
        ]);
    }

    public function test_professional_role_code_used_by_another_record_is_rejected(): void
    {
        $user = $this->userWithPermission('professional-roles.update');
        $professionalRole = ProfessionalRole::factory()->create(['code' => 'ROLE-TARGET', 'name' => 'Target Role']);
        ProfessionalRole::factory()->create(['code' => 'ROLE-TAKEN']);

        $this->actingAs($user)
            ->from(route('admin.professional-roles.index'))
            ->put(route('admin.professional-roles.update', $professionalRole), $this->professionalRolePayload('ROLE-TAKEN', 'Invalid Update'))
            ->assertSessionHasErrors('code');

        $this->assertDatabaseHas('professional_roles', [
            'id' => $professionalRole->id,
            'code' => 'ROLE-TARGET',
            'name' => 'Target Role',
        ]);
    }

    public function test_professional_role_policy_receives_the_route_bound_model(): void
    {
        Gate::policy(ProfessionalRole::class, RecordingProfessionalRolePolicy::class);
        $user = $this->userWithoutPermissions();
        $professionalRole = ProfessionalRole::factory()->create(['code' => 'ROLE-BOUND']);

        $this->actingAs($user)
            ->put(route('admin.professional-roles.update', $professionalRole), $this->professionalRolePayload('ROLE-BOUND', 'Bound Role'))
            ->assertRedirect();

        $this->assertTrue(RecordingProfessionalRolePolicy::$received?->is($professionalRole) ?? false);
        $this->assertDatabaseHas('professional_roles', ['id' => $professionalRole->id, 'name' => 'Bound Role']);
    }

    private function userWithPermission(string $permission): User
    {
        $user = $this->userWithoutPermissions();
        $user->givePermissionTo($permission);

        return $user;
    }

    private function userWithoutPermissions(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        return User::factory()->create(['email_verified_at' => now()]);
    }

    /**
     * @return array{code: string, name: string, description: null, daily_capacity_minutes: int, shift_count: int, is_active: bool}
     */
    private function factoryUnitPayload(string $code, string $name): array
    {
        return [
            'code' => $code,
            'name' => $name,
            'description' => null,
            'daily_capacity_minutes' => 480,
            'shift_count' => 1,
            'is_active' => true,
        ];
    }

    /**
     * @return array{code: string, name: string, description: null, is_active: bool}
     */
    private function professionalRolePayload(string $code, string $name): array
    {
        return [
            'code' => $code,
            'name' => $name,
            'description' => null,
            'is_active' => true,
        ];
    }
}

class RecordingFactoryUnitPolicy
{
    public static ?FactoryUnit $received = null;

    public function update(User $user, FactoryUnit $factoryUnit): bool
    {
        self::$received = $factoryUnit;

        return true;
    }
}

class RecordingProfessionalRolePolicy
{
    public static ?ProfessionalRole $received = null;

    public function update(User $user, ProfessionalRole $professionalRole): bool
    {
        self::$received = $professionalRole;

        return true;
    }
}
