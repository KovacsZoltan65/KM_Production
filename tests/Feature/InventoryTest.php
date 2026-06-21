<?php

namespace Tests\Feature;

use App\Enums\StockMovementType;
use App\Models\CustomerOrderItem;
use App\Models\Item;
use App\Models\ItemBatch;
use App\Models\ItemInstance;
use App\Models\Location;
use App\Models\MaterialRequirement;
use App\Models\ProductionOrder;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\StockReservation;
use Database\Seeders\InventorySeeder;
use Database\Seeders\ItemMasterDataSeeder;
use Database\Seeders\OrderProductionSeeder;
use Database\Seeders\ProductionMasterDataSeeder;
use Database\Seeders\ProductionStructureSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_material_requirement_belongs_to_customer_order_item(): void
    {
        $customerOrderItem = CustomerOrderItem::factory()->create();
        $requirement = MaterialRequirement::factory()->create([
            'customer_order_item_id' => $customerOrderItem->id,
        ]);

        $this->assertTrue($requirement->customerOrderItem->is($customerOrderItem));
    }

    public function test_material_requirement_belongs_to_required_item(): void
    {
        $item = Item::factory()->purchasedMaterial()->create();
        $requirement = MaterialRequirement::factory()->create([
            'required_item_id' => $item->id,
            'unit' => $item->unit,
        ]);

        $this->assertTrue($requirement->requiredItem->is($item));
    }

    public function test_stock_balance_belongs_to_item_and_location(): void
    {
        $item = Item::factory()->purchasedMaterial()->create();
        $location = Location::factory()->create();
        $balance = StockBalance::factory()->create([
            'item_id' => $item->id,
            'location_id' => $location->id,
        ]);

        $this->assertTrue($balance->item->is($item));
        $this->assertTrue($balance->location->is($location));
    }

    public function test_stock_balance_supports_batch_stock(): void
    {
        $batch = ItemBatch::factory()->create();
        $balance = StockBalance::factory()->create([
            'item_id' => $batch->item_id,
            'item_batch_id' => $batch->id,
        ]);

        $this->assertTrue($balance->itemBatch->is($batch));
    }

    public function test_same_item_location_and_batch_balance_can_exist_only_once(): void
    {
        $batch = ItemBatch::factory()->create();
        $location = Location::factory()->create();

        StockBalance::factory()->create([
            'item_id' => $batch->item_id,
            'location_id' => $location->id,
            'item_batch_id' => $batch->id,
        ]);

        $this->expectException(QueryException::class);

        StockBalance::factory()->create([
            'item_id' => $batch->item_id,
            'location_id' => $location->id,
            'item_batch_id' => $batch->id,
        ]);
    }

    public function test_stock_reservation_can_connect_to_customer_order_item(): void
    {
        $customerOrderItem = CustomerOrderItem::factory()->create();
        $reservation = StockReservation::factory()->create([
            'item_id' => $customerOrderItem->item_id,
            'customer_order_item_id' => $customerOrderItem->id,
            'production_order_id' => null,
        ]);

        $this->assertTrue($reservation->customerOrderItem->is($customerOrderItem));
    }

    public function test_stock_reservation_can_connect_to_production_order(): void
    {
        $productionOrder = ProductionOrder::factory()->create();
        $reservation = StockReservation::factory()->create([
            'item_id' => $productionOrder->item_id,
            'customer_order_item_id' => null,
            'production_order_id' => $productionOrder->id,
        ]);

        $this->assertTrue($reservation->productionOrder->is($productionOrder));
    }

    public function test_stock_movement_belongs_to_item(): void
    {
        $item = Item::factory()->purchasedMaterial()->create();
        $movement = StockMovement::factory()->create(['item_id' => $item->id]);

        $this->assertTrue($movement->item->is($item));
    }

    public function test_stock_movement_supports_from_and_to_locations(): void
    {
        $fromLocation = Location::factory()->create();
        $toLocation = Location::factory()->create();

        $movement = StockMovement::factory()->create([
            'from_location_id' => $fromLocation->id,
            'to_location_id' => $toLocation->id,
            'movement_type' => StockMovementType::Transfer,
        ]);

        $this->assertTrue($movement->fromLocation->is($fromLocation));
        $this->assertTrue($movement->toLocation->is($toLocation));
    }

    public function test_stock_movement_supports_batch_relation(): void
    {
        $batch = ItemBatch::factory()->create();

        $movement = StockMovement::factory()->create([
            'item_id' => $batch->item_id,
            'item_batch_id' => $batch->id,
        ]);

        $this->assertTrue($movement->itemBatch->is($batch));
    }

    public function test_stock_movement_supports_item_instance_relation(): void
    {
        $itemInstance = ItemInstance::factory()->create();

        $movement = StockMovement::factory()->create([
            'item_id' => $itemInstance->item_id,
            'item_instance_id' => $itemInstance->id,
            'movement_type' => StockMovementType::ProductionOutput,
        ]);

        $this->assertTrue($movement->itemInstance->is($itemInstance));
    }

    public function test_inventory_seeder_is_idempotent(): void
    {
        $this->seed(ProductionMasterDataSeeder::class);
        $this->seed(ItemMasterDataSeeder::class);
        $this->seed(ProductionStructureSeeder::class);
        $this->seed(OrderProductionSeeder::class);
        $this->seed(InventorySeeder::class);
        $this->seed(InventorySeeder::class);

        $this->assertDatabaseCount('item_batches', 3);
        $this->assertDatabaseCount('stock_balances', 5);

        $mainWarehouseId = Location::query()->where('code', 'MAIN-WH')->value('id');
        $paintId = Item::query()->where('item_number', 'PAINT-BLACK')->value('id');

        $this->assertDatabaseHas('stock_balances', [
            'item_id' => $paintId,
            'location_id' => $mainWarehouseId,
            'quantity' => 25,
        ]);
    }
}
