<?php

namespace Tests\Feature;

use App\Enums\ItemType;
use App\Models\FactoryUnit;
use App\Models\Item;
use App\Models\ItemBatch;
use App\Models\ItemInstance;
use App\Models\ItemInstanceRelation;
use App\Models\Location;
use App\Models\SerialSequence;
use App\Models\Supplier;
use Database\Seeders\ItemMasterDataSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ItemMasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_items_table_is_created(): void
    {
        $this->assertTrue(Schema::hasTable('items'));
    }

    public function test_purchased_material_factory_does_not_require_serial_number(): void
    {
        $item = Item::factory()->purchasedMaterial()->create();

        $this->assertSame(ItemType::PurchasedMaterial, $item->item_type);
        $this->assertFalse($item->requires_serial_number);
        $this->assertFalse($item->requiresSerialNumberByType());
    }

    public function test_manufactured_part_factory_requires_serial_number(): void
    {
        $item = Item::factory()->manufacturedPart()->create();

        $this->assertSame(ItemType::ManufacturedPart, $item->item_type);
        $this->assertTrue($item->requires_serial_number);
        $this->assertTrue($item->requiresSerialNumberByType());
    }

    public function test_item_batch_belongs_to_item_and_supplier_is_nullable(): void
    {
        $item = Item::factory()->purchasedMaterial()->create();

        $batchWithoutSupplier = ItemBatch::factory()->create([
            'item_id' => $item->id,
            'supplier_id' => null,
        ]);

        $this->assertTrue($batchWithoutSupplier->item->is($item));
        $this->assertNull($batchWithoutSupplier->supplier);

        $supplier = Supplier::factory()->create();
        $batchWithSupplier = ItemBatch::factory()->create([
            'item_id' => $item->id,
            'supplier_id' => $supplier->id,
        ]);

        $this->assertTrue($batchWithSupplier->supplier->is($supplier));
    }

    public function test_serial_sequence_prefix_and_year_must_be_unique(): void
    {
        SerialSequence::factory()->create([
            'prefix' => 'HEG',
            'year' => 2026,
        ]);

        $this->expectException(QueryException::class);

        SerialSequence::factory()->create([
            'prefix' => 'HEG',
            'year' => 2026,
        ]);
    }

    public function test_item_instance_serial_number_must_be_unique(): void
    {
        ItemInstance::factory()->create(['serial_number' => 'HEG/2026/0001']);

        $this->expectException(QueryException::class);

        ItemInstance::factory()->create(['serial_number' => 'HEG/2026/0001']);
    }

    public function test_item_instance_belongs_to_item_factory_unit_and_location(): void
    {
        $item = Item::factory()->manufacturedPart()->create();
        $factoryUnit = FactoryUnit::factory()->create();
        $location = Location::factory()->create(['factory_unit_id' => $factoryUnit->id]);

        $instance = ItemInstance::factory()->create([
            'item_id' => $item->id,
            'factory_unit_id' => $factoryUnit->id,
            'current_location_id' => $location->id,
        ]);

        $this->assertTrue($instance->item->is($item));
        $this->assertTrue($instance->factoryUnit->is($factoryUnit));
        $this->assertTrue($instance->currentLocation->is($location));
    }

    public function test_item_instance_relation_stores_parent_child_relation(): void
    {
        $parent = ItemInstance::factory()->create();
        $child = ItemInstance::factory()->create();

        $relation = ItemInstanceRelation::factory()->create([
            'parent_item_instance_id' => $parent->id,
            'child_item_instance_id' => $child->id,
            'quantity' => 2,
        ]);

        $this->assertTrue($relation->parent->is($parent));
        $this->assertTrue($relation->child->is($child));
        $this->assertTrue($parent->childRelations->contains($relation));
        $this->assertTrue($child->parentRelations->contains($relation));
        $this->assertSame('2.000', $relation->quantity);
    }

    public function test_same_child_can_be_linked_to_same_parent_only_once(): void
    {
        $parent = ItemInstance::factory()->create();
        $child = ItemInstance::factory()->create();

        ItemInstanceRelation::factory()->create([
            'parent_item_instance_id' => $parent->id,
            'child_item_instance_id' => $child->id,
        ]);

        $this->expectException(QueryException::class);

        ItemInstanceRelation::factory()->create([
            'parent_item_instance_id' => $parent->id,
            'child_item_instance_id' => $child->id,
        ]);
    }

    public function test_item_master_data_seeder_is_idempotent(): void
    {
        $this->seed(ItemMasterDataSeeder::class);
        $this->seed(ItemMasterDataSeeder::class);

        $this->assertDatabaseCount('items', 9);

        $this->assertDatabaseHas('items', [
            'item_number' => 'SCR-M4X20',
            'item_type' => ItemType::PurchasedMaterial->value,
            'requires_serial_number' => false,
        ]);

        $this->assertDatabaseHas('items', [
            'item_number' => 'PRODUCT-AAA',
            'item_type' => ItemType::FinishedProduct->value,
            'requires_serial_number' => true,
        ]);
    }
}
