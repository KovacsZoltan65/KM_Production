<?php

namespace Tests\Feature;

use App\Enums\MaterialRequirementStatus;
use App\Enums\StockMovementType;
use App\Enums\StockReservationStatus;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderItem;
use App\Models\Item;
use App\Models\Location;
use App\Models\MaterialRequirement;
use App\Models\OperationSequence;
use App\Models\ProductionOrder;
use App\Models\ProductionPlan;
use App\Models\ProductionPlanItem;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\StockReservation;
use App\Models\User;
use App\Services\Admin\MaterialRequirementService;
use App\Services\Admin\StockReservationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class InventoryManagementUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_super_admin_can_access_stock_balances(): void
    {
        $user = $this->verifiedUser('super-admin');

        $this->actingAs($user)
            ->get(route('admin.inventory.stock-balances.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Admin/Inventory/StockBalances/Index'));
    }

    public function test_user_without_permission_cannot_access_inventory_page(): void
    {
        $user = $this->verifiedUser();

        $this->actingAs($user)
            ->get(route('admin.inventory.stock-balances.index'))
            ->assertForbidden();
    }

    public function test_stock_balances_search_works(): void
    {
        $user = $this->verifiedUser('super-admin');
        $matchingItem = Item::factory()->purchasedMaterial()->create(['item_number' => 'MAT-SEARCH-1', 'name' => 'Search steel']);
        $otherItem = Item::factory()->purchasedMaterial()->create(['item_number' => 'MAT-OTHER-1', 'name' => 'Other steel']);
        $location = Location::factory()->create();

        StockBalance::factory()->create(['item_id' => $matchingItem->id, 'location_id' => $location->id, 'quantity' => 12]);
        StockBalance::factory()->create(['item_id' => $otherItem->id, 'location_id' => $location->id, 'quantity' => 8]);

        $this->actingAs($user)
            ->get(route('admin.inventory.stock-balances.index', ['search' => 'MAT-SEARCH']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('records.data.0.item.item_number', 'MAT-SEARCH-1')
                ->missing('records.data.1'));
    }

    public function test_stock_movements_filter_works(): void
    {
        $user = $this->verifiedUser('super-admin');
        $item = Item::factory()->purchasedMaterial()->create();

        StockMovement::factory()->create(['item_id' => $item->id, 'movement_type' => StockMovementType::Transfer]);
        StockMovement::factory()->create(['item_id' => $item->id, 'movement_type' => StockMovementType::Correction]);

        $this->actingAs($user)
            ->get(route('admin.inventory.stock-movements.index', ['movement_type' => StockMovementType::Transfer->value]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('records.data.0.movement_type', StockMovementType::Transfer->value)
                ->missing('records.data.1'));
    }

    public function test_material_requirement_calculation_is_bom_based(): void
    {
        ['productionOrder' => $productionOrder, 'material' => $material] = $this->productionOrderFixture(bomQuantity: 2, orderQuantity: 5);

        app(MaterialRequirementService::class)->calculateForProductionOrder($productionOrder);

        $this->assertDatabaseHas('material_requirements', [
            'customer_order_item_id' => $productionOrder->customer_order_item_id,
            'required_item_id' => $material->id,
            'required_quantity' => 10,
        ]);
    }

    public function test_available_quantity_respects_active_reservations(): void
    {
        ['productionOrder' => $productionOrder, 'material' => $material, 'location' => $location] = $this->productionOrderFixture();
        StockBalance::factory()->create(['item_id' => $material->id, 'location_id' => $location->id, 'quantity' => 10]);
        StockReservation::factory()->create([
            'item_id' => $material->id,
            'location_id' => $location->id,
            'reserved_quantity' => 3,
            'status' => StockReservationStatus::Active,
        ]);

        app(MaterialRequirementService::class)->calculateForProductionOrder($productionOrder);

        $this->assertDatabaseHas('material_requirements', [
            'customer_order_item_id' => $productionOrder->customer_order_item_id,
            'required_item_id' => $material->id,
            'available_quantity' => 7,
        ]);
    }

    public function test_missing_quantity_is_calculated_correctly(): void
    {
        ['productionOrder' => $productionOrder, 'material' => $material, 'location' => $location] = $this->productionOrderFixture(bomQuantity: 2, orderQuantity: 5);
        StockBalance::factory()->create(['item_id' => $material->id, 'location_id' => $location->id, 'quantity' => 6]);

        app(MaterialRequirementService::class)->calculateForProductionOrder($productionOrder);

        $this->assertDatabaseHas('material_requirements', [
            'customer_order_item_id' => $productionOrder->customer_order_item_id,
            'required_item_id' => $material->id,
            'missing_quantity' => 4,
            'status' => MaterialRequirementStatus::Missing->value,
        ]);
    }

    public function test_reserve_for_production_order_creates_reservations(): void
    {
        ['productionOrder' => $productionOrder, 'material' => $material, 'location' => $location] = $this->productionOrderFixture(bomQuantity: 2, orderQuantity: 5);
        StockBalance::factory()->create(['item_id' => $material->id, 'location_id' => $location->id, 'quantity' => 15]);

        app(StockReservationService::class)->reserveForProductionOrder($productionOrder);

        $this->assertDatabaseHas('stock_reservations', [
            'item_id' => $material->id,
            'production_order_id' => $productionOrder->id,
            'reserved_quantity' => 10,
            'status' => StockReservationStatus::Active->value,
        ]);
    }

    public function test_partial_reservation_works_with_shortage(): void
    {
        ['productionOrder' => $productionOrder, 'material' => $material, 'location' => $location] = $this->productionOrderFixture(bomQuantity: 2, orderQuantity: 5);
        StockBalance::factory()->create(['item_id' => $material->id, 'location_id' => $location->id, 'quantity' => 4]);

        app(StockReservationService::class)->reserveForProductionOrder($productionOrder);

        $this->assertDatabaseHas('stock_reservations', [
            'item_id' => $material->id,
            'production_order_id' => $productionOrder->id,
            'reserved_quantity' => 4,
        ]);
        $this->assertDatabaseHas('material_requirements', [
            'required_item_id' => $material->id,
            'reserved_quantity' => 4,
            'missing_quantity' => 6,
            'status' => MaterialRequirementStatus::PartiallyAvailable->value,
        ]);
    }

    public function test_release_reservation_works(): void
    {
        $user = $this->verifiedUser('warehouse-manager');
        $reservation = StockReservation::factory()->create(['status' => StockReservationStatus::Active]);

        $this->actingAs($user)
            ->patch(route('admin.inventory.stock-reservations.release', $reservation))
            ->assertRedirect();

        $this->assertDatabaseHas('stock_reservations', [
            'id' => $reservation->id,
            'status' => StockReservationStatus::Released->value,
        ]);
    }

    public function test_released_reservation_is_not_counted_active(): void
    {
        ['productionOrder' => $productionOrder, 'material' => $material, 'location' => $location] = $this->productionOrderFixture(bomQuantity: 2, orderQuantity: 5);
        StockBalance::factory()->create(['item_id' => $material->id, 'location_id' => $location->id, 'quantity' => 10]);
        StockReservation::factory()->create([
            'item_id' => $material->id,
            'location_id' => $location->id,
            'reserved_quantity' => 6,
            'status' => StockReservationStatus::Released,
        ]);

        app(MaterialRequirementService::class)->calculateForProductionOrder($productionOrder);

        $this->assertDatabaseHas('material_requirements', [
            'required_item_id' => $material->id,
            'available_quantity' => 10,
            'missing_quantity' => 0,
        ]);
    }

    public function test_shortages_view_only_returns_missing_requirements(): void
    {
        $user = $this->verifiedUser('super-admin');
        $missing = MaterialRequirement::factory()->create(['missing_quantity' => 3]);
        MaterialRequirement::factory()->create(['missing_quantity' => 0]);

        $this->actingAs($user)
            ->get(route('admin.inventory.shortages.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('records.data.0.id', $missing->id)
                ->missing('records.data.1'));
    }

    public function test_audit_log_is_created_on_reserve(): void
    {
        $user = $this->verifiedUser('warehouse-manager');
        ['productionOrder' => $productionOrder, 'material' => $material, 'location' => $location] = $this->productionOrderFixture();
        StockBalance::factory()->create(['item_id' => $material->id, 'location_id' => $location->id, 'quantity' => 10]);

        app(StockReservationService::class)->reserveForProductionOrder($productionOrder, $user);

        $this->assertDatabaseHas('activity_log', [
            'event' => 'stock_reserved',
            'subject_type' => ProductionOrder::class,
            'subject_id' => $productionOrder->id,
            'causer_id' => $user->id,
        ]);
    }

    public function test_audit_log_is_created_on_release(): void
    {
        $user = $this->verifiedUser('warehouse-manager');
        $reservation = StockReservation::factory()->create(['status' => StockReservationStatus::Active]);

        app(StockReservationService::class)->release($reservation, $user);

        $activity = Activity::query()
            ->where('event', 'stock_reservation_released')
            ->firstOrFail();

        $this->assertTrue($activity->subject->is($reservation));
        $this->assertTrue($activity->causer->is($user));
    }

    private function verifiedUser(?string $role = null): User
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        if ($role !== null) {
            $user->assignRole($role);
        }

        return $user;
    }

    /**
     * @return array{productionOrder: ProductionOrder, material: Item, location: Location}
     */
    private function productionOrderFixture(float $bomQuantity = 1, float $orderQuantity = 10): array
    {
        $finishedItem = Item::factory()->finishedProduct()->create(['unit' => 'db']);
        $material = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
        $location = Location::factory()->create();
        $bom = Bom::factory()->create(['item_id' => $finishedItem->id]);

        BomItem::factory()->create([
            'bom_id' => $bom->id,
            'item_id' => $material->id,
            'quantity' => $bomQuantity,
            'unit' => $material->unit,
        ]);

        $customerOrder = CustomerOrder::factory()->create();
        $customerOrderItem = CustomerOrderItem::factory()->create([
            'customer_order_id' => $customerOrder->id,
            'item_id' => $finishedItem->id,
            'quantity' => $orderQuantity,
            'unit' => $finishedItem->unit,
        ]);
        $productionPlan = ProductionPlan::factory()->create(['customer_order_id' => $customerOrder->id]);
        $operationSequence = OperationSequence::factory()->create(['item_id' => $finishedItem->id]);
        $productionPlanItem = ProductionPlanItem::factory()->create([
            'production_plan_id' => $productionPlan->id,
            'customer_order_item_id' => $customerOrderItem->id,
            'item_id' => $finishedItem->id,
            'bom_id' => $bom->id,
            'operation_sequence_id' => $operationSequence->id,
            'quantity' => $orderQuantity,
        ]);

        $productionOrder = ProductionOrder::factory()->create([
            'production_plan_item_id' => $productionPlanItem->id,
            'customer_order_item_id' => $customerOrderItem->id,
            'item_id' => $finishedItem->id,
            'bom_id' => $bom->id,
            'operation_sequence_id' => $operationSequence->id,
            'quantity' => $orderQuantity,
        ]);

        return [
            'productionOrder' => $productionOrder,
            'material' => $material,
            'location' => $location,
        ];
    }
}
