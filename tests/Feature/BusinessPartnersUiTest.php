<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class BusinessPartnersUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_customers_index(): void
    {
        $this->get('/admin/customers')->assertRedirect('/login');
    }

    public function test_super_admin_can_access_customers_index(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/admin/customers')
            ->assertOk();
    }

    public function test_user_without_permission_cannot_access_customers_index(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->get('/admin/customers')
            ->assertForbidden();
    }

    public function test_customer_can_be_created(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->post('/admin/customers', $this->customerPayload([
                'code' => 'CUST-UI-001',
                'name' => 'UI Customer',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'code' => 'CUST-UI-001',
            'name' => 'UI Customer',
        ]);
    }

    public function test_customer_can_be_updated(): void
    {
        $admin = $this->superAdmin();
        $customer = Customer::factory()->create(['code' => 'CUST-UI-002']);

        $this->actingAs($admin)
            ->put("/admin/customers/{$customer->id}", $this->customerPayload([
                'code' => 'CUST-UI-002',
                'name' => 'Updated Customer',
                'is_active' => false,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Customer',
            'is_active' => false,
        ]);
    }

    public function test_customer_can_be_deleted(): void
    {
        $admin = $this->superAdmin();
        $customer = Customer::factory()->create();

        $this->actingAs($admin)
            ->delete("/admin/customers/{$customer->id}")
            ->assertRedirect();

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }

    public function test_customer_code_unique_validation_works(): void
    {
        $admin = $this->superAdmin();
        Customer::factory()->create(['code' => 'CUST-UNIQUE']);

        $this->actingAs($admin)
            ->post('/admin/customers', $this->customerPayload(['code' => 'CUST-UNIQUE']))
            ->assertSessionHasErrors('code');
    }

    public function test_supplier_can_be_created(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->post('/admin/suppliers', $this->supplierPayload([
                'code' => 'SUP-UI-001',
                'name' => 'UI Supplier',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('suppliers', [
            'code' => 'SUP-UI-001',
            'name' => 'UI Supplier',
        ]);
    }

    public function test_supplier_can_be_updated(): void
    {
        $admin = $this->superAdmin();
        $supplier = Supplier::factory()->create(['code' => 'SUP-UI-002']);

        $this->actingAs($admin)
            ->put("/admin/suppliers/{$supplier->id}", $this->supplierPayload([
                'code' => 'SUP-UI-002',
                'name' => 'Updated Supplier',
                'is_active' => false,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Updated Supplier',
            'is_active' => false,
        ]);
    }

    public function test_supplier_can_be_deleted(): void
    {
        $admin = $this->superAdmin();
        $supplier = Supplier::factory()->create();

        $this->actingAs($admin)
            ->delete("/admin/suppliers/{$supplier->id}")
            ->assertRedirect();

        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
    }

    public function test_supplier_code_unique_validation_works(): void
    {
        $admin = $this->superAdmin();
        Supplier::factory()->create(['code' => 'SUP-UNIQUE']);

        $this->actingAs($admin)
            ->post('/admin/suppliers', $this->supplierPayload(['code' => 'SUP-UNIQUE']))
            ->assertSessionHasErrors('code');
    }

    public function test_server_side_search_works_on_customers_list(): void
    {
        $admin = $this->superAdmin();
        Customer::factory()->create(['code' => 'CUST-SEARCH', 'name' => 'Needle Customer']);
        Customer::factory()->create(['code' => 'CUST-OTHER', 'name' => 'Other Customer']);

        $response = $this->actingAs($admin)->get('/admin/customers?search=Needle');

        $response->assertOk();
        $response->assertSee('Needle Customer');
        $response->assertDontSee('Other Customer');
    }

    public function test_server_side_search_works_on_suppliers_list(): void
    {
        $admin = $this->superAdmin();
        Supplier::factory()->create(['code' => 'SUP-SEARCH', 'name' => 'Needle Supplier']);
        Supplier::factory()->create(['code' => 'SUP-OTHER', 'name' => 'Other Supplier']);

        $response = $this->actingAs($admin)->get('/admin/suppliers?search=Needle');

        $response->assertOk();
        $response->assertSee('Needle Supplier');
        $response->assertDontSee('Other Supplier');
    }

    public function test_activity_log_is_created_for_customer_modification(): void
    {
        $admin = $this->superAdmin();
        $customer = Customer::factory()->create(['code' => 'CUST-LOG']);

        $this->actingAs($admin)
            ->put("/admin/customers/{$customer->id}", $this->customerPayload([
                'code' => 'CUST-LOG',
                'name' => 'Logged Customer Update',
            ]))
            ->assertRedirect();

        $this->assertTrue(Activity::query()->where('event', 'admin_customer_updated')->exists());
    }

    public function test_activity_log_is_created_for_supplier_modification(): void
    {
        $admin = $this->superAdmin();
        $supplier = Supplier::factory()->create(['code' => 'SUP-LOG']);

        $this->actingAs($admin)
            ->put("/admin/suppliers/{$supplier->id}", $this->supplierPayload([
                'code' => 'SUP-LOG',
                'name' => 'Logged Supplier Update',
            ]))
            ->assertRedirect();

        $this->assertTrue(Activity::query()->where('event', 'admin_supplier_updated')->exists());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function customerPayload(array $overrides = []): array
    {
        return array_merge([
            'code' => 'CUST-DEFAULT',
            'name' => 'Default Customer',
            'tax_number' => '12345678-1-42',
            'email' => 'customer@example.com',
            'phone' => '+36 1 111 1111',
            'billing_address' => 'Billing address',
            'shipping_address' => 'Shipping address',
            'notes' => 'Customer notes',
            'is_active' => true,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function supplierPayload(array $overrides = []): array
    {
        return array_merge([
            'code' => 'SUP-DEFAULT',
            'name' => 'Default Supplier',
            'tax_number' => '87654321-1-42',
            'email' => 'supplier@example.com',
            'phone' => '+36 1 222 2222',
            'address' => 'Supplier address',
            'notes' => 'Supplier notes',
            'is_active' => true,
        ], $overrides);
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
