<?php

namespace Tests\Feature;

use App\Enums\ProductionTaskStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\QualityCheckResult;
use App\Enums\StockReservationStatus;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Item;
use App\Models\Location;
use App\Models\OperationType;
use App\Models\ProductionTask;
use App\Models\PurchaseOrder;
use App\Models\StockBalance;
use App\Models\StockReservation;
use App\Models\Supplier;
use App\Models\User;
use App\Repositories\Contracts\ReportingRepositoryInterface;
use App\Services\Admin\CustomerAdminService;
use App\Services\Admin\CustomerOrderService;
use App\Services\Admin\GoodsReceiptService;
use App\Services\Admin\ItemAdminService;
use App\Services\Admin\OperationTypeAdminService;
use App\Services\Admin\ProductionTaskMaterialService;
use App\Services\Admin\ProductionTaskService;
use App\Services\Admin\PurchaseOrderService;
use App\Services\Admin\QualityCheckService;
use App\Services\Admin\ReportingService;
use App\Services\Admin\StockReservationService;
use App\Services\Admin\SupplierAdminService;
use App\Services\Admin\SupplierPerformanceService;
use App\Services\BusinessCacheInvalidator;
use App\Support\Cache\BusinessCacheDomain;
use App\Support\Cache\BusinessCacheKey;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use LogicException;
use Mockery;
use Mockery\CompositeExpectation;
use Mockery\MockInterface;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BusinessCacheInvalidationTest extends TestCase
{
    use DatabaseMigrations;

    protected string|false $seeder = false;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_cache_keys_are_deterministic_and_filter_scoped(): void
    {
        $first = BusinessCacheKey::customerOrdersReport([
            'status' => 'draft',
            'customer_id' => 12,
            'channels' => ['wholesale', 'retail'],
        ]);
        $reordered = BusinessCacheKey::customerOrdersReport([
            'channels' => ['retail', 'wholesale'],
            'customer_id' => 12,
            'status' => 'draft',
        ]);
        $differentCustomer = BusinessCacheKey::customerOrdersReport([
            'customer_id' => 13,
            'status' => 'draft',
            'channels' => ['retail', 'wholesale'],
        ]);

        $this->assertSame($first, $reordered);
        $this->assertNotSame($first, $differentCustomer);
        $this->assertStringStartsWith('km-production:reports-customer-orders:g1:summary:', $first);
    }

    public function test_cache_keys_distinguish_null_empty_missing_and_scope_parameters(): void
    {
        $missing = BusinessCacheKey::customerOrdersReport();
        $null = BusinessCacheKey::customerOrdersReport(['status' => null]);
        $empty = BusinessCacheKey::customerOrdersReport(['status' => '']);
        $scoped = BusinessCacheKey::customerOrdersReport([
            'locale' => 'hu',
            'user_id' => 10,
            'factory_unit_id' => 20,
        ]);

        $this->assertNotSame($missing, $null);
        $this->assertNotSame($missing, $empty);
        $this->assertNotSame($null, $empty);
        $this->assertNotSame($scoped, BusinessCacheKey::customerOrdersReport([
            'locale' => 'en',
            'user_id' => 10,
            'factory_unit_id' => 20,
        ]));
        $this->assertNotSame($scoped, BusinessCacheKey::customerOrdersReport([
            'locale' => 'hu',
            'user_id' => 11,
            'factory_unit_id' => 20,
        ]));
        $this->assertNotSame($scoped, BusinessCacheKey::customerOrdersReport([
            'locale' => 'hu',
            'user_id' => 10,
            'factory_unit_id' => 21,
        ]));
    }

    public function test_customer_order_creation_invalidates_an_empty_filtered_report(): void
    {
        $customer = Customer::factory()->create();
        $item = Item::factory()->finishedProduct()->create();
        $reporting = app(ReportingService::class);

        $before = $reporting->customerOrdersSummary(['customer_id' => $customer->id]);
        $cachedKey = BusinessCacheKey::customerOrdersReport([
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
        $this->assertNotSame($cachedKey, BusinessCacheKey::customerOrdersReport([
            'customer_id' => $customer->id,
        ]));
    }

    public function test_posting_a_goods_receipt_refreshes_inventory_and_intelligence_domains(): void
    {
        $item = Item::factory()->create();
        $location = Location::factory()->create();
        $reporting = app(ReportingService::class);
        $forecastKey = BusinessCacheKey::materialForecast();

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
        $this->assertNotSame($forecastKey, BusinessCacheKey::materialForecast());
    }

    public function test_material_consumption_refreshes_the_cached_inventory_result(): void
    {
        $item = Item::factory()->purchasedMaterial()->create(['unit' => 'pcs']);
        $location = Location::factory()->create();
        $task = ProductionTask::factory()->create(['status' => ProductionTaskStatus::InProgress]);
        StockBalance::factory()->create([
            'item_id' => $item->id,
            'location_id' => $location->id,
            'quantity' => 10,
        ]);
        $reporting = app(ReportingService::class);

        $this->assertSame(10.0, data_get($reporting->inventorySummary(), 'rows.0.current_stock'));
        $generation = BusinessCacheKey::generation(BusinessCacheDomain::ReportsInventory);
        $this->assertSame(0, DB::transactionLevel());

        app(ProductionTaskMaterialService::class)->store($task, [
            'item_id' => $item->id,
            'location_id' => $location->id,
            'used_quantity' => 4,
            'unit' => 'pcs',
        ]);

        $this->assertSame(0, DB::transactionLevel());
        $this->assertGreaterThan(
            $generation,
            BusinessCacheKey::generation(BusinessCacheDomain::ReportsInventory),
        );
        $this->assertSame(6.0, data_get($reporting->inventorySummary(), 'rows.0.current_stock'));
    }

    public function test_item_update_refreshes_the_cached_inventory_label(): void
    {
        $item = Item::factory()->purchasedMaterial()->create(['name' => 'Old item']);
        $location = Location::factory()->create();
        StockBalance::factory()->create([
            'item_id' => $item->id,
            'location_id' => $location->id,
            'quantity' => 10,
        ]);
        $reporting = app(ReportingService::class);

        $this->assertStringContainsString(
            'Old item',
            (string) data_get($reporting->inventorySummary(), 'rows.0.item'),
        );

        app(ItemAdminService::class)->update($item, [
            'name' => 'New item',
            'item_type' => $item->item_type,
        ]);

        $this->assertStringContainsString(
            'New item',
            (string) data_get($reporting->inventorySummary(), 'rows.0.item'),
        );
    }

    public function test_releasing_a_stock_reservation_refreshes_the_cached_inventory_result(): void
    {
        $item = Item::factory()->purchasedMaterial()->create();
        $location = Location::factory()->create();
        StockBalance::factory()->create([
            'item_id' => $item->id,
            'location_id' => $location->id,
            'quantity' => 10,
        ]);
        $reservation = StockReservation::factory()->create([
            'item_id' => $item->id,
            'location_id' => $location->id,
            'reserved_quantity' => 4,
            'status' => StockReservationStatus::Active,
        ]);
        $reporting = app(ReportingService::class);

        $this->assertSame(4.0, data_get($reporting->inventorySummary(), 'rows.0.reserved'));

        app(StockReservationService::class)->release($reservation);

        $this->assertSame(0.0, data_get($reporting->inventorySummary(), 'rows.0.reserved'));
    }

    public function test_purchase_order_status_change_refreshes_the_cached_procurement_result(): void
    {
        $supplier = Supplier::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id,
            'status' => PurchaseOrderStatus::Ordered,
        ]);
        $reporting = app(ReportingService::class);

        $this->assertSame(1, data_get($reporting->procurementSummary(), 'rows.0.open'));

        app(PurchaseOrderService::class)->close($purchaseOrder);

        $this->assertSame(0, data_get($reporting->procurementSummary(), 'rows.0.open'));
        $this->assertSame(1, data_get($reporting->procurementSummary(), 'rows.0.closed'));
    }

    public function test_supplier_update_refreshes_supplier_performance_without_rotating_inventory(): void
    {
        $supplier = Supplier::factory()->create(['name' => 'Old Supplier']);
        $performance = app(SupplierPerformanceService::class);
        $inventoryKey = BusinessCacheKey::inventoryReport();

        $this->assertNotNull(collect($performance->analyze()['rows'])->firstWhere('supplier', 'Old Supplier'));

        app(SupplierAdminService::class)->update($supplier, ['name' => 'New Supplier']);

        $this->assertNotNull(collect($performance->analyze()['rows'])->firstWhere('supplier', 'New Supplier'));
        $this->assertSame($inventoryKey, BusinessCacheKey::inventoryReport());
    }

    public function test_customer_name_change_does_not_rotate_the_unrelated_capacity_domain(): void
    {
        $customer = Customer::factory()->create(['name' => 'Old Customer']);
        $capacityKey = BusinessCacheKey::capacityDashboard();
        $riskKey = BusinessCacheKey::productionRisks();

        app(CustomerAdminService::class)->update($customer, ['name' => 'New Customer']);

        $this->assertSame($capacityKey, BusinessCacheKey::capacityDashboard());
        $this->assertNotSame($riskKey, BusinessCacheKey::productionRisks());
    }

    public function test_operation_type_change_rotates_the_capacity_domain(): void
    {
        $operationType = OperationType::factory()->create();
        $capacityKey = BusinessCacheKey::capacitySchedule();

        app(OperationTypeAdminService::class)->update($operationType, [
            'name' => 'Renamed operation',
        ]);

        $this->assertNotSame($capacityKey, BusinessCacheKey::capacitySchedule());
    }

    public function test_production_task_finish_refreshes_production_and_capacity_domains(): void
    {
        $task = ProductionTask::factory()->create(['status' => ProductionTaskStatus::InProgress]);
        $reporting = app(ReportingService::class);
        $capacityKey = BusinessCacheKey::capacityDashboard();

        $before = collect($reporting->productionSummary()['rows'])
            ->firstWhere('id', $task->production_order_id);

        app(ProductionTaskService::class)->finish($task);

        $after = collect($reporting->productionSummary()['rows'])
            ->firstWhere('id', $task->production_order_id);

        $this->assertNotSame($before['completed_percent'], $after['completed_percent']);
        $this->assertNotSame($capacityKey, BusinessCacheKey::capacityDashboard());
    }

    public function test_quality_check_refreshes_quality_report_and_trends(): void
    {
        $task = ProductionTask::factory()->create(['status' => ProductionTaskStatus::WaitingForCheck]);
        $inspector = Employee::factory()->create();
        $reporting = app(ReportingService::class);
        $trendKey = BusinessCacheKey::qualityTrends();

        $this->assertSame([], $reporting->qualitySummary()['rows']);

        app(QualityCheckService::class)->store($task, [
            'checked_by' => $inspector->id,
            'result' => QualityCheckResult::Rejected->value,
        ]);

        $this->assertCount(1, $reporting->qualitySummary()['rows']);
        $this->assertNotSame($trendKey, BusinessCacheKey::qualityTrends());
    }

    public function test_invalidation_is_idempotent_and_does_not_rotate_unrelated_domains(): void
    {
        $invalidator = app(BusinessCacheInvalidator::class);
        $procurementKey = BusinessCacheKey::procurementReport();
        $inventoryKey = BusinessCacheKey::inventoryReport();

        $invalidator->inventoryChanged();
        $invalidator->inventoryChanged();

        $this->assertSame($procurementKey, BusinessCacheKey::procurementReport());
        $this->assertNotSame($inventoryKey, BusinessCacheKey::inventoryReport());
    }

    public function test_cache_hit_and_post_invalidation_recalculation_contract(): void
    {
        $repository = Mockery::mock(
            ReportingRepositoryInterface::class,
            static function (MockInterface $mock): void {
                $expectation = $mock->shouldReceive('inventorySummary');

                if (! $expectation instanceof CompositeExpectation) {
                    throw new LogicException('Mockery did not create a concrete method expectation.');
                }

                $expectation->__call('times', [2]);
                $expectation->andReturn(['marker' => 'first'], ['marker' => 'second']);
            },
        );

        if (! $repository instanceof ReportingRepositoryInterface) {
            throw new LogicException('The reporting mock does not implement its contract.');
        }

        $reporting = new ReportingService($repository);

        $this->assertSame('first', $reporting->inventorySummary()['marker']);
        $this->assertSame('first', $reporting->inventorySummary()['marker']);

        app(BusinessCacheInvalidator::class)->inventoryChanged();

        $this->assertSame('second', $reporting->inventorySummary()['marker']);
        $this->assertSame('second', $reporting->inventorySummary()['marker']);
    }

    public function test_rolled_back_business_change_does_not_rotate_cache_generation(): void
    {
        $item = Item::factory()->purchasedMaterial()->create();
        $location = Location::factory()->create();
        $balance = StockBalance::factory()->create([
            'item_id' => $item->id,
            'location_id' => $location->id,
            'quantity' => 10,
        ]);
        $reporting = app(ReportingService::class);
        $generation = BusinessCacheKey::generation(BusinessCacheDomain::ReportsInventory);

        $this->assertSame(10.0, data_get($reporting->inventorySummary(), 'rows.0.current_stock'));

        DB::beginTransaction();
        $balance->decrement('quantity', 4);
        app(BusinessCacheInvalidator::class)->inventoryChanged();
        DB::rollBack();

        $this->assertSame(
            $generation,
            BusinessCacheKey::generation(BusinessCacheDomain::ReportsInventory),
        );
        $this->assertSame(10.0, (float) $balance->fresh()->quantity);
        $this->assertSame(10.0, data_get($reporting->inventorySummary(), 'rows.0.current_stock'));
    }

    public function test_nested_transaction_rollback_keeps_data_and_cache_generation_unchanged(): void
    {
        $customer = Customer::factory()->create();
        $item = Item::factory()->finishedProduct()->create();
        $generation = BusinessCacheKey::generation(BusinessCacheDomain::ReportsCustomerOrders);

        DB::beginTransaction();
        app(CustomerOrderService::class)->create([
            'customer_id' => $customer->id,
            'items' => [[
                'item_id' => $item->id,
                'quantity' => 1,
                'unit' => $item->unit,
            ]],
        ]);
        DB::rollBack();

        $this->assertDatabaseCount('customer_orders', 0);
        $this->assertSame(
            $generation,
            BusinessCacheKey::generation(BusinessCacheDomain::ReportsCustomerOrders),
        );
    }

    public function test_cache_failure_after_commit_does_not_rollback_business_data(): void
    {
        $customer = Customer::factory()->create();
        $item = Item::factory()->finishedProduct()->create();
        Cache::shouldReceive('add')
            ->once()
            ->andThrow(new \RuntimeException('Cache store unavailable.'));

        $failure = null;

        try {
            app(CustomerOrderService::class)->create([
                'customer_id' => $customer->id,
                'items' => [[
                    'item_id' => $item->id,
                    'quantity' => 1,
                    'unit' => $item->unit,
                ]],
            ]);
        } catch (\Throwable $exception) {
            $failure = $exception;
        }

        $this->assertNotNull($failure, 'A hibás cache-generációnak kivételt kellett volna okoznia.');
        $this->assertDatabaseCount('customer_orders', 1);
        $this->assertDatabaseCount('customer_order_items', 1);
    }

    public function test_generation_contract_also_works_with_the_file_cache_driver(): void
    {
        Cache::setDefaultDriver('file');
        Cache::flush();

        try {
            $before = BusinessCacheKey::inventoryReport();
            Cache::put($before, ['rows' => []], 60);

            app(BusinessCacheInvalidator::class)->inventoryChanged();

            $after = BusinessCacheKey::inventoryReport();
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
