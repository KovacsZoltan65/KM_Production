<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Item;
use App\Models\Location;
use App\Models\User;
use App\Services\Admin\CustomerOrderService;
use App\Services\Admin\GoodsReceiptService;
use App\Services\Admin\ReportingService;
use App\Services\BusinessCacheInvalidator;
use App\Support\Cache\BusinessCacheDomain;
use App\Support\Cache\BusinessCacheKey;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BusinessCacheInvalidationTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_cache_keys_are_deterministic_and_filter_scoped(): void
    {
        $first = BusinessCacheKey::make(BusinessCacheDomain::ReportsCustomerOrders, 'summary', [
            'status' => 'draft',
            'customer_id' => 12,
            'date_from' => null,
        ]);
        $reordered = BusinessCacheKey::make(BusinessCacheDomain::ReportsCustomerOrders, 'summary', [
            'customer_id' => 12,
            'status' => 'draft',
        ]);
        $differentCustomer = BusinessCacheKey::make(BusinessCacheDomain::ReportsCustomerOrders, 'summary', [
            'customer_id' => 13,
            'status' => 'draft',
        ]);

        $this->assertSame($first, $reordered);
        $this->assertNotSame($first, $differentCustomer);
        $this->assertStringStartsWith('km-production:reports-customer-orders:g1:summary:', $first);
    }

    public function test_customer_order_creation_invalidates_an_empty_filtered_report(): void
    {
        $customer = Customer::factory()->create();
        $item = Item::factory()->create();
        $reporting = app(ReportingService::class);

        $before = $reporting->customerOrdersSummary(['customer_id' => $customer->id]);
        $cachedKey = BusinessCacheKey::make(BusinessCacheDomain::ReportsCustomerOrders, 'summary', [
            'customer_id' => $customer->id,
        ]);

        $this->assertSame([], $before['rows']);
        $this->assertTrue(Cache::has($cachedKey));

        app(CustomerOrderService::class)->create([
            'customer_id' => $customer->id,
            'items' => [[
                'item_id' => $item->id,
                'quantity' => 2,
                'unit' => $item->unit,
            ]],
        ]);

        $after = $reporting->customerOrdersSummary(['customer_id' => $customer->id]);

        $this->assertCount(1, $after['rows']);
        $this->assertSame($customer->name, data_get($after, 'rows.0.customer'));
        $this->assertNotSame($cachedKey, BusinessCacheKey::make(
            BusinessCacheDomain::ReportsCustomerOrders,
            'summary',
            ['customer_id' => $customer->id],
        ));
    }

    public function test_posting_a_goods_receipt_refreshes_inventory_and_intelligence_domains(): void
    {
        $item = Item::factory()->create();
        $location = Location::factory()->create();
        $reporting = app(ReportingService::class);
        $forecastKey = BusinessCacheKey::make(BusinessCacheDomain::IntelligenceMaterialForecast, 'forecast');

        $this->assertSame([], $reporting->inventorySummary()['rows']);
        Cache::put($forecastKey, ['rows' => []], 300);

        $receipt = app(GoodsReceiptService::class)->create([
            'items' => [[
                'item_id' => $item->id,
                'location_id' => $location->id,
                'quantity' => 7,
            ]],
        ]);
        app(GoodsReceiptService::class)->post($receipt);

        $inventory = $reporting->inventorySummary();

        $this->assertCount(1, $inventory['rows']);
        $this->assertSame(7.0, data_get($inventory, 'rows.0.current_stock'));
        $this->assertNotSame($forecastKey, BusinessCacheKey::make(
            BusinessCacheDomain::IntelligenceMaterialForecast,
            'forecast',
        ));
    }

    public function test_invalidation_is_idempotent_and_does_not_rotate_unrelated_domains(): void
    {
        $invalidator = app(BusinessCacheInvalidator::class);
        $procurementKey = BusinessCacheKey::make(BusinessCacheDomain::ReportsProcurement, 'summary');
        $inventoryKey = BusinessCacheKey::make(BusinessCacheDomain::ReportsInventory, 'summary');

        $invalidator->inventoryChanged();
        $invalidator->inventoryChanged();

        $this->assertSame($procurementKey, BusinessCacheKey::make(
            BusinessCacheDomain::ReportsProcurement,
            'summary',
        ));
        $this->assertNotSame($inventoryKey, BusinessCacheKey::make(
            BusinessCacheDomain::ReportsInventory,
            'summary',
        ));
    }

    public function test_rolled_back_business_change_does_not_rotate_cache_generation(): void
    {
        $generation = BusinessCacheKey::generation(BusinessCacheDomain::ReportsInventory);

        DB::beginTransaction();
        app(BusinessCacheInvalidator::class)->inventoryChanged();
        DB::rollBack();

        $this->assertSame(
            $generation,
            BusinessCacheKey::generation(BusinessCacheDomain::ReportsInventory),
        );
    }

    public function test_generation_contract_also_works_with_the_file_cache_driver(): void
    {
        Cache::setDefaultDriver('file');
        Cache::flush();

        try {
            $before = BusinessCacheKey::make(BusinessCacheDomain::ReportsInventory, 'summary');
            Cache::put($before, ['rows' => []], 60);

            app(BusinessCacheInvalidator::class)->inventoryChanged();

            $after = BusinessCacheKey::make(BusinessCacheDomain::ReportsInventory, 'summary');
            $this->assertNotSame($before, $after);
            $this->assertTrue(Cache::has($before));
            $this->assertFalse(Cache::has($after));
        } finally {
            Cache::flush();
            Cache::setDefaultDriver('array');
        }
    }

    public function test_spatie_permission_cache_updates_immediately_after_role_changes(): void
    {
        $permission = Permission::create(['name' => 'cache-contract-view', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'cache-contract-role', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->assertFalse($user->can($permission->name));

        $role->givePermissionTo($permission);
        $this->assertTrue($user->fresh()->can($permission->name));

        $role->revokePermissionTo($permission);
        $this->assertFalse($user->fresh()->can($permission->name));
    }
}
