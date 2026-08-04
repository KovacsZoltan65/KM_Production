<?php

namespace Tests\Feature;

use App\Enums\OperationTypeCode;
use App\Enums\PurchaseRequisitionStatus;
use App\Enums\StockMovementType;
use App\Enums\StockReservationStatus;
use App\Models\Customer;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderItem;
use App\Models\FactoryUnit;
use App\Models\Item;
use App\Models\Location;
use App\Models\MaterialRequirement;
use App\Models\OperationType;
use App\Models\ProfessionalRole;
use App\Models\PurchaseRequisition;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\StockReservation;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
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

    public function test_shortages_index_supports_records_only_partial_reload(): void
    {
        $requiredItem = Item::factory()->purchasedMaterial()->create([
            'item_number' => 'REFRESH-SHORTAGE',
        ]);
        MaterialRequirement::factory()->create([
            'required_item_id' => $requiredItem->id,
            'missing_quantity' => 9,
            'status' => 'missing',
        ]);
        MaterialRequirement::factory()->create([
            'missing_quantity' => 0,
            'status' => 'missing',
        ]);

        $url = "/admin/inventory/shortages?required_item_id={$requiredItem->id}&status=missing&per_page=25&page=1";

        $this->actingAs($this->superAdmin())
            ->get($url)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Inventory/Shortages/Index')
                ->has('records.data', 1)
                ->where('records.data.0.required_item.item_number', 'REFRESH-SHORTAGE')
                ->where('records.per_page', 25)
                ->where('records.current_page', 1)
                ->where('filters.required_item_id', (string) $requiredItem->id)
                ->where('filters.status', 'missing')
                ->reloadOnly('records', fn (AssertableInertia $reload) => $reload
                    ->has('records.data', 1)
                    ->where('records.data.0.required_item.item_number', 'REFRESH-SHORTAGE')
                    ->where('records.per_page', 25)
                    ->where('records.current_page', 1)
                    ->missing('filters')));
    }

    public function test_material_requirements_index_supports_records_only_partial_reload(): void
    {
        $customerOrder = CustomerOrder::factory()->create([
            'order_number' => 'REFRESH-MR-ORDER',
        ]);
        $customerOrderItem = CustomerOrderItem::factory()->create([
            'customer_order_id' => $customerOrder->id,
        ]);
        $requiredItem = Item::factory()->purchasedMaterial()->create([
            'item_number' => 'REFRESH-MR-ITEM',
        ]);
        $otherItem = Item::factory()->purchasedMaterial()->create();

        MaterialRequirement::factory()->create([
            'customer_order_item_id' => $customerOrderItem->id,
            'required_item_id' => $requiredItem->id,
            'required_quantity' => 17,
            'status' => 'received',
        ]);
        MaterialRequirement::factory()->create([
            'customer_order_item_id' => $customerOrderItem->id,
            'required_item_id' => $requiredItem->id,
            'status' => 'missing',
        ]);
        MaterialRequirement::factory()->create([
            'customer_order_item_id' => $customerOrderItem->id,
            'required_item_id' => $otherItem->id,
            'status' => 'received',
        ]);

        $url = "/admin/inventory/material-requirements?status=received&required_item_id={$requiredItem->id}&customer_order_id={$customerOrder->id}&per_page=25&sort=required_quantity&direction=desc&page=1";

        $this->actingAs($this->superAdmin())
            ->get($url)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Inventory/MaterialRequirements/Index')
                ->has('records.data', 1)
                ->where('records.data.0.required_item.item_number', 'REFRESH-MR-ITEM')
                ->where('records.data.0.customer_order_item.customer_order.order_number', 'REFRESH-MR-ORDER')
                ->where('records.per_page', 25)
                ->where('records.current_page', 1)
                ->where('filters.status', 'received')
                ->where('filters.required_item_id', (string) $requiredItem->id)
                ->where('filters.customer_order_id', (string) $customerOrder->id)
                ->has('statusOptions')
                ->has('itemOptions')
                ->has('customerOrderOptions')
                ->reloadOnly('records', fn (AssertableInertia $reload) => $reload
                    ->has('records.data', 1)
                    ->where('records.data.0.required_item.item_number', 'REFRESH-MR-ITEM')
                    ->where('records.data.0.customer_order_item.customer_order.order_number', 'REFRESH-MR-ORDER')
                    ->where('records.per_page', 25)
                    ->where('records.current_page', 1)
                    ->missing('filters')
                    ->missing('statusOptions')
                    ->missing('itemOptions')
                    ->missing('customerOrderOptions')));
    }

    public function test_stock_reservations_index_supports_records_only_partial_reload(): void
    {
        $matchingItem = Item::factory()->purchasedMaterial()->create([
            'item_number' => 'REFRESH-RESERVATION',
        ]);
        $matchingReservation = StockReservation::factory()->create([
            'item_id' => $matchingItem->id,
            'reserved_quantity' => 17,
            'status' => StockReservationStatus::Active,
        ]);
        StockReservation::factory()->create([
            'reserved_quantity' => 99,
            'status' => StockReservationStatus::Released,
        ]);

        $url = '/admin/inventory/stock-reservations?status=active&per_page=25&sort=reserved_quantity&direction=desc&page=1';

        $this->actingAs($this->superAdmin())
            ->get($url)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Inventory/StockReservations/Index')
                ->has('records.data', 1)
                ->where('records.data.0.id', $matchingReservation->id)
                ->where('records.data.0.item.item_number', 'REFRESH-RESERVATION')
                ->where('records.per_page', 25)
                ->where('records.current_page', 1)
                ->where('filters.status', 'active')
                ->where('filters.sort', 'reserved_quantity')
                ->where('filters.direction', 'desc')
                ->has('statusOptions')
                ->reloadOnly('records', fn (AssertableInertia $reload) => $reload
                    ->has('records.data', 1)
                    ->where('records.data.0.id', $matchingReservation->id)
                    ->where('records.data.0.item.item_number', 'REFRESH-RESERVATION')
                    ->where('records.per_page', 25)
                    ->where('records.current_page', 1)
                    ->missing('filters')
                    ->missing('statusOptions')));
    }

    public function test_stock_movements_index_supports_read_only_records_partial_reload(): void
    {
        $matchingItem = Item::factory()->purchasedMaterial()->create([
            'item_number' => 'REFRESH-MOVEMENT',
        ]);
        $otherItem = Item::factory()->purchasedMaterial()->create();
        $location = Location::factory()->create();
        $otherLocation = Location::factory()->create();
        $stockBalance = StockBalance::factory()->create([
            'item_id' => $matchingItem->id,
            'location_id' => $location->id,
            'quantity' => 50,
        ]);
        $matchingMovement = StockMovement::factory()->create([
            'item_id' => $matchingItem->id,
            'to_location_id' => $location->id,
            'quantity' => 17,
            'movement_type' => StockMovementType::Correction,
            'performed_at' => '2026-01-15 23:59:59',
            'notes' => 'REFRESH-MOVEMENT-MATCH',
        ]);
        StockMovement::factory()->create([
            'item_id' => $matchingItem->id,
            'to_location_id' => $location->id,
            'movement_type' => StockMovementType::Transfer,
            'performed_at' => '2026-01-15 12:00:00',
            'notes' => 'REFRESH-MOVEMENT-WRONG-TYPE',
        ]);
        StockMovement::factory()->create([
            'item_id' => $otherItem->id,
            'to_location_id' => $otherLocation->id,
            'movement_type' => StockMovementType::Correction,
            'performed_at' => '2026-01-15 12:00:00',
            'notes' => 'REFRESH-MOVEMENT-WRONG-RELATIONS',
        ]);
        $activityCount = Activity::query()
            ->where('subject_type', StockMovement::class)
            ->where('subject_id', $matchingMovement->id)
            ->count();

        $url = "/admin/inventory/stock-movements?movement_type=correction&item_id={$matchingItem->id}&location_id={$location->id}&date_from=2026-01-15&date_to=2026-01-15&per_page=25&sort=quantity&direction=desc&page=1";

        $this->actingAs($this->superAdmin())
            ->get($url)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Inventory/StockMovements/Index')
                ->has('records.data', 1)
                ->where('records.data.0.id', $matchingMovement->id)
                ->where('records.data.0.item.item_number', 'REFRESH-MOVEMENT')
                ->where('records.data.0.to_location.id', $location->id)
                ->where('records.data.0.quantity', '17.000')
                ->where('records.data.0.movement_type', StockMovementType::Correction->value)
                ->where('records.per_page', 25)
                ->where('records.current_page', 1)
                ->where('filters.movement_type', StockMovementType::Correction->value)
                ->where('filters.item_id', (string) $matchingItem->id)
                ->where('filters.location_id', (string) $location->id)
                ->where('filters.date_from', '2026-01-15')
                ->where('filters.date_to', '2026-01-15')
                ->where('filters.sort', 'quantity')
                ->where('filters.direction', 'desc')
                ->has('movementTypeOptions')
                ->has('itemOptions')
                ->has('locationOptions')
                ->reloadOnly('records', fn (AssertableInertia $reload) => $reload
                    ->has('records.data', 1)
                    ->where('records.data.0.id', $matchingMovement->id)
                    ->where('records.data.0.quantity', '17.000')
                    ->where('records.per_page', 25)
                    ->where('records.current_page', 1)
                    ->missing('filters')
                    ->missing('movementTypeOptions')
                    ->missing('itemOptions')
                    ->missing('locationOptions')));

        $this->actingAs($this->superAdmin())
            ->get(route('admin.inventory.stock-movements.index', [
                'movement_type' => 'not-a-real-movement-type',
                'item_id' => $matchingItem->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('records.data', 0));

        $this->assertSame(1, StockMovement::query()->where('notes', 'REFRESH-MOVEMENT-MATCH')->count());
        $this->assertDatabaseHas('stock_movements', [
            'id' => $matchingMovement->id,
            'quantity' => 17,
            'movement_type' => StockMovementType::Correction->value,
            'performed_at' => '2026-01-15 23:59:59',
        ]);
        $this->assertSame('50.000', $stockBalance->fresh()->quantity);
        $this->assertSame(
            $activityCount,
            Activity::query()
                ->where('subject_type', StockMovement::class)
                ->where('subject_id', $matchingMovement->id)
                ->count(),
        );
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

    public function test_purchase_requisitions_index_supports_records_only_partial_reload(): void
    {
        $admin = $this->superAdmin();
        Item::factory()->purchasedMaterial()->create([
            'item_number' => 'REFRESH-REQUISITION-ITEM',
        ]);
        $matchingRequisition = PurchaseRequisition::factory()->create([
            'requisition_number' => 'REFRESH-REQUISITION-001',
            'status' => PurchaseRequisitionStatus::Requested,
            'requested_by' => $admin->id,
            'requested_at' => '2026-01-15 12:00:00',
            'notes' => 'Matching partial refresh requisition',
        ]);
        $matchingRequisition->items()->create([
            'item_id' => Item::factory()->purchasedMaterial()->create()->id,
            'quantity' => 4,
            'unit' => 'db',
        ]);
        PurchaseRequisition::factory()->create([
            'requisition_number' => 'REFRESH-REQUISITION-APPROVED',
            'status' => PurchaseRequisitionStatus::Approved,
        ]);
        PurchaseRequisition::factory()->create([
            'requisition_number' => 'UNRELATED-REQUISITION',
            'status' => PurchaseRequisitionStatus::Requested,
        ]);

        $url = '/admin/purchase-requisitions?search=REFRESH-REQUISITION-001&status=requested&per_page=25&sort=requisition_number&direction=desc&page=1';

        $this->actingAs($admin)
            ->get($url)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/PurchaseRequisitions/Index')
                ->has('records.data', 1)
                ->where('records.data.0.id', $matchingRequisition->id)
                ->where('records.data.0.requisition_number', 'REFRESH-REQUISITION-001')
                ->where('records.data.0.status', PurchaseRequisitionStatus::Requested->value)
                ->where('records.data.0.items_count', 1)
                ->where('records.data.0.requester.id', $admin->id)
                ->where('records.per_page', 25)
                ->where('records.current_page', 1)
                ->where('filters.search', 'REFRESH-REQUISITION-001')
                ->where('filters.status', PurchaseRequisitionStatus::Requested->value)
                ->where('filters.sort', 'requisition_number')
                ->where('filters.direction', 'desc')
                ->has('statusOptions')
                ->has('itemOptions')
                ->reloadOnly('records', fn (AssertableInertia $reload) => $reload
                    ->has('records.data', 1)
                    ->where('records.data.0.id', $matchingRequisition->id)
                    ->where('records.data.0.items_count', 1)
                    ->where('records.per_page', 25)
                    ->where('records.current_page', 1)
                    ->missing('filters')
                    ->missing('statusOptions')
                    ->missing('itemOptions')));
    }

    public function test_factory_units_index_supports_records_only_partial_reload(): void
    {
        FactoryUnit::factory()->create(['code' => 'REFRESH-FACTORY', 'name' => 'Refresh Factory']);

        $this->assertRecordsOnlyReload(
            '/admin/factory-units?search=REFRESH-FACTORY&per_page=25&sort=name&direction=desc',
            'Admin/FactoryUnits/Index',
            'records.data.0.code',
            'REFRESH-FACTORY',
        );
    }

    public function test_locations_index_supports_records_only_partial_reload(): void
    {
        $factoryUnit = FactoryUnit::factory()->create();
        Location::factory()->create([
            'factory_unit_id' => $factoryUnit->id,
            'code' => 'REFRESH-LOCATION',
            'name' => 'Refresh Location',
        ]);

        $this->assertRecordsOnlyReload(
            '/admin/locations?search=REFRESH-LOCATION&per_page=25&sort=name&direction=desc',
            'Admin/Locations/Index',
            'records.data.0.code',
            'REFRESH-LOCATION',
            ['options'],
        );
    }

    public function test_professional_roles_index_supports_records_only_partial_reload(): void
    {
        ProfessionalRole::factory()->create(['code' => 'REFRESH-PROFESSIONAL', 'name' => 'Refresh Professional Role']);

        $this->assertRecordsOnlyReload(
            '/admin/professional-roles?search=REFRESH-PROFESSIONAL&per_page=25&sort=name&direction=desc',
            'Admin/ProfessionalRoles/Index',
            'records.data.0.code',
            'REFRESH-PROFESSIONAL',
        );
    }

    public function test_operation_types_index_supports_records_only_partial_reload(): void
    {
        OperationType::factory()->create([
            'code' => OperationTypeCode::CUTTING,
            'name' => 'Refresh Operation Type',
        ]);

        $this->assertRecordsOnlyReload(
            '/admin/operation-types?search=Refresh%20Operation&per_page=25&sort=name&direction=desc',
            'Admin/OperationTypes/Index',
            'records.data.0.name',
            'Refresh Operation Type',
            ['operationTypeCodes'],
        );
    }

    public function test_users_index_supports_records_only_partial_reload(): void
    {
        User::factory()->create([
            'name' => 'Refresh User',
            'email' => 'refresh-user@example.test',
            'email_verified_at' => now(),
        ]);

        $this->assertRecordsOnlyReload(
            '/admin/users?search=refresh-user%40example.test&per_page=25&sort=name&direction=desc',
            'Admin/Users/Index',
            'records.data.0.email',
            'refresh-user@example.test',
            ['options'],
        );
    }

    public function test_roles_index_supports_records_only_partial_reload(): void
    {
        Role::query()->create(['name' => 'REFRESH-ROLE', 'guard_name' => 'web']);

        $this->assertRecordsOnlyReload(
            '/admin/roles?search=REFRESH-ROLE&per_page=25&sort=name&direction=desc',
            'Admin/Roles/Index',
            'records.data.0.name',
            'REFRESH-ROLE',
            ['options'],
        );
    }

    public function test_permissions_index_supports_records_only_partial_reload(): void
    {
        Permission::query()->create(['name' => 'REFRESH-PERMISSION', 'guard_name' => 'web']);

        $this->assertRecordsOnlyReload(
            '/admin/permissions?search=REFRESH-PERMISSION&per_page=25&sort=name&direction=desc',
            'Admin/Permissions/Index',
            'records.data.0.name',
            'REFRESH-PERMISSION',
        );
    }

    /**
     * @param  list<string>  $optionProps
     */
    private function assertRecordsOnlyReload(
        string $url,
        string $component,
        string $recordPath,
        mixed $expectedRecordValue,
        array $optionProps = [],
    ): void {
        $pageAssertion = fn (AssertableInertia $page) => $page
            ->component($component)
            ->has('records.data', 1)
            ->has('filters');

        $this->actingAs($this->superAdmin())
            ->get($url)
            ->assertOk()
            ->assertInertia(function (AssertableInertia $page) use (
                $pageAssertion,
                $optionProps,
                $recordPath,
                $expectedRecordValue,
            ): void {
                $pageAssertion($page);

                foreach ($optionProps as $optionProp) {
                    $page->has($optionProp);
                }

                $page->reloadOnly('records', function (AssertableInertia $reload) use (
                    $optionProps,
                    $recordPath,
                    $expectedRecordValue,
                ): void {
                    $reload
                        ->has('records.data', 1)
                        ->where($recordPath, $expectedRecordValue)
                        ->missing('filters');

                    foreach ($optionProps as $optionProp) {
                        $reload->missing($optionProp);
                    }
                });
            });
    }

    private function superAdmin(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('super-admin');

        return $user;
    }
}
