<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerOrder;
use App\Models\Item;
use App\Models\Location;
use App\Models\StockBalance;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AdminIndexPartialReloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_items_index_supports_records_only_partial_reload(): void
    {
        $admin = $this->superAdmin();
        Item::factory()->purchasedMaterial()->create(['item_number' => 'REFRESH-ITEM', 'name' => 'Refresh Item']);

        $this->actingAs($admin)
            ->get('/admin/items?search=REFRESH-ITEM&per_page=25&sort=name&direction=desc')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Items/Index')
                ->has('records.data', 1)
                ->has('filters')
                ->has('itemTypes')
                ->reloadOnly('records', fn (AssertableInertia $reload) => $reload
                    ->has('records.data', 1)
                    ->where('records.data.0.item_number', 'REFRESH-ITEM')
                    ->missing('filters')
                    ->missing('itemTypes')));
    }

    public function test_customers_index_supports_records_only_partial_reload(): void
    {
        $admin = $this->superAdmin();
        Customer::factory()->create(['code' => 'REFRESH-CUSTOMER', 'name' => 'Refresh Customer']);

        $this->actingAs($admin)
            ->get('/admin/customers?search=REFRESH-CUSTOMER&per_page=25&sort=name&direction=desc')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Customers/Index')
                ->has('records.data', 1)
                ->has('filters')
                ->reloadOnly('records', fn (AssertableInertia $reload) => $reload
                    ->has('records.data', 1)
                    ->where('records.data.0.code', 'REFRESH-CUSTOMER')
                    ->missing('filters')));
    }

    public function test_suppliers_index_supports_records_only_partial_reload(): void
    {
        $admin = $this->superAdmin();
        Supplier::factory()->create(['code' => 'REFRESH-SUPPLIER', 'name' => 'Refresh Supplier']);

        $this->actingAs($admin)
            ->get('/admin/suppliers?search=REFRESH-SUPPLIER&per_page=25&sort=name&direction=desc')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Suppliers/Index')
                ->has('records.data', 1)
                ->has('filters')
                ->reloadOnly('records', fn (AssertableInertia $reload) => $reload
                    ->has('records.data', 1)
                    ->where('records.data.0.code', 'REFRESH-SUPPLIER')
                    ->missing('filters')));
    }

    public function test_stock_balances_index_supports_records_only_partial_reload(): void
    {
        $admin = $this->superAdmin();
        $item = Item::factory()->purchasedMaterial()->create(['item_number' => 'REFRESH-STOCK']);
        $location = Location::factory()->create();
        StockBalance::factory()->create([
            'item_id' => $item->id,
            'location_id' => $location->id,
            'quantity' => 12,
        ]);

        $this->actingAs($admin)
            ->get('/admin/inventory/stock-balances?search=REFRESH-STOCK&per_page=25&sort=quantity&direction=desc')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Inventory/StockBalances/Index')
                ->has('records.data', 1)
                ->has('filters')
                ->reloadOnly('records', fn (AssertableInertia $reload) => $reload
                    ->has('records.data', 1)
                    ->where('records.data.0.item.item_number', 'REFRESH-STOCK')
                    ->missing('filters')));
    }

    public function test_customer_orders_index_supports_records_only_partial_reload(): void
    {
        $admin = $this->superAdmin();
        $customer = Customer::factory()->create(['code' => 'REFRESH-ORDER-CUSTOMER']);
        CustomerOrder::factory()->create([
            'order_number' => 'REFRESH-ORDER',
            'customer_id' => $customer->id,
        ]);
        Item::factory()->finishedProduct()->create();

        $this->actingAs($admin)
            ->get('/admin/customer-orders?search=REFRESH-ORDER&per_page=25&sort=order_number&direction=desc')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/CustomerOrders/Index')
                ->has('records.data', 1)
                ->has('filters')
                ->has('customerOptions')
                ->has('itemOptions')
                ->has('statusOptions')
                ->reloadOnly('records', fn (AssertableInertia $reload) => $reload
                    ->has('records.data', 1)
                    ->where('records.data.0.order_number', 'REFRESH-ORDER')
                    ->missing('filters')
                    ->missing('customerOptions')
                    ->missing('itemOptions')
                    ->missing('statusOptions')));
    }

    private function superAdmin(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('super-admin');

        return $user;
    }
}
