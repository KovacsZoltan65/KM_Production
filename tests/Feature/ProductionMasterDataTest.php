<?php

namespace Tests\Feature;

use App\Enums\LocationType;
use App\Models\Employee;
use App\Models\FactoryUnit;
use App\Models\Location;
use App\Models\ProfessionalRole;
use App\Models\User;
use Database\Seeders\ProductionMasterDataSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductionMasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_data_tables_are_created(): void
    {
        foreach ([
            'factory_units',
            'locations',
            'professional_roles',
            'employees',
            'customers',
            'suppliers',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Expected table [{$table}] to exist.");
        }
    }

    public function test_factory_unit_code_must_be_unique(): void
    {
        FactoryUnit::factory()->create(['code' => 'HEG']);

        $this->expectException(QueryException::class);

        FactoryUnit::factory()->create(['code' => 'HEG']);
    }

    public function test_location_can_belong_to_factory_unit(): void
    {
        $factoryUnit = FactoryUnit::factory()->create();

        $location = Location::factory()->create([
            'factory_unit_id' => $factoryUnit->id,
            'location_type' => LocationType::Workshop,
        ]);

        $this->assertTrue($location->factoryUnit->is($factoryUnit));
        $this->assertTrue($factoryUnit->locations->contains($location));
        $this->assertSame(LocationType::Workshop, $location->location_type);
    }

    public function test_employee_can_belong_to_professional_role(): void
    {
        $professionalRole = ProfessionalRole::factory()->create();

        $employee = Employee::factory()->create([
            'professional_role_id' => $professionalRole->id,
        ]);

        $this->assertTrue($employee->professionalRole->is($professionalRole));
        $this->assertTrue($professionalRole->employees->contains($employee));
    }

    public function test_employee_user_relation_is_nullable(): void
    {
        $employeeWithoutUser = Employee::factory()->create(['user_id' => null]);

        $this->assertNull($employeeWithoutUser->user);

        $user = User::factory()->create();
        $employeeWithUser = Employee::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($employeeWithUser->user->is($user));
    }

    public function test_production_master_data_seeder_is_idempotent(): void
    {
        $this->seed(ProductionMasterDataSeeder::class);
        $this->seed(ProductionMasterDataSeeder::class);

        $this->assertDatabaseCount('factory_units', 4);
        $this->assertDatabaseCount('professional_roles', 4);
        $this->assertDatabaseCount('locations', 7);

        $this->assertDatabaseHas('factory_units', [
            'code' => 'HEG',
            'name' => 'Hegesztő műhely',
        ]);

        $this->assertDatabaseHas('locations', [
            'code' => 'MEO-QA',
            'location_type' => LocationType::QualityArea->value,
        ]);
    }
}
