<?php

namespace Tests\Feature;

use App\Enums\OperationTypeCode;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\FactoryUnit;
use App\Models\Item;
use App\Models\OperationSequence;
use App\Models\OperationSequenceStep;
use App\Models\OperationType;
use App\Models\ProfessionalRole;
use Database\Seeders\ItemMasterDataSeeder;
use Database\Seeders\ProductionMasterDataSeeder;
use Database\Seeders\ProductionStructureSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductionStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_boms_table_is_created(): void
    {
        $this->assertTrue(Schema::hasTable('boms'));
    }

    public function test_bom_item_belongs_to_bom(): void
    {
        $bom = Bom::factory()->create();
        $component = Item::factory()->purchasedMaterial()->create();

        $bomItem = BomItem::factory()->create([
            'bom_id' => $bom->id,
            'item_id' => $component->id,
        ]);

        $this->assertTrue($bomItem->bom->is($bom));
        $this->assertTrue($bomItem->item->is($component));
        $this->assertTrue($bom->bomItems->contains($bomItem));
    }

    public function test_same_item_can_appear_in_same_bom_only_once(): void
    {
        $bom = Bom::factory()->create();
        $component = Item::factory()->purchasedMaterial()->create();

        BomItem::factory()->create([
            'bom_id' => $bom->id,
            'item_id' => $component->id,
        ]);

        $this->expectException(QueryException::class);

        BomItem::factory()->create([
            'bom_id' => $bom->id,
            'item_id' => $component->id,
        ]);
    }

    public function test_item_can_have_multiple_bom_versions(): void
    {
        $item = Item::factory()->finishedProduct()->create();

        $v1 = Bom::factory()->create(['item_id' => $item->id, 'version' => 1]);
        $v2 = Bom::factory()->create(['item_id' => $item->id, 'version' => 2]);

        $this->assertTrue($v1->item->is($item));
        $this->assertTrue($v2->item->is($item));
        $this->assertDatabaseCount('boms', 2);
    }

    public function test_operation_type_code_must_be_unique(): void
    {
        OperationType::factory()->create(['code' => OperationTypeCode::WELDING]);

        $this->expectException(QueryException::class);

        OperationType::factory()->create(['code' => OperationTypeCode::WELDING]);
    }

    public function test_item_can_have_multiple_operation_sequence_versions(): void
    {
        $item = Item::factory()->finishedProduct()->create();

        $v1 = OperationSequence::factory()->create(['item_id' => $item->id, 'version' => 1]);
        $v2 = OperationSequence::factory()->create(['item_id' => $item->id, 'version' => 2]);

        $this->assertTrue($v1->item->is($item));
        $this->assertTrue($v2->item->is($item));
        $this->assertDatabaseCount('operation_sequences', 2);
    }

    public function test_step_order_is_unique_within_operation_sequence(): void
    {
        $operationSequence = OperationSequence::factory()->create();

        OperationSequenceStep::factory()->create([
            'operation_sequence_id' => $operationSequence->id,
            'step_order' => 1,
        ]);

        $this->expectException(QueryException::class);

        OperationSequenceStep::factory()->create([
            'operation_sequence_id' => $operationSequence->id,
            'step_order' => 1,
        ]);
    }

    public function test_step_belongs_to_factory_unit(): void
    {
        $factoryUnit = FactoryUnit::factory()->create();
        $step = OperationSequenceStep::factory()->create(['factory_unit_id' => $factoryUnit->id]);

        $this->assertTrue($step->factoryUnit->is($factoryUnit));
    }

    public function test_step_belongs_to_professional_role(): void
    {
        $professionalRole = ProfessionalRole::factory()->create();
        $step = OperationSequenceStep::factory()->create(['professional_role_id' => $professionalRole->id]);

        $this->assertTrue($step->professionalRole->is($professionalRole));
    }

    public function test_production_structure_seeder_is_idempotent(): void
    {
        $this->seed(ProductionMasterDataSeeder::class);
        $this->seed(ItemMasterDataSeeder::class);
        $this->seed(ProductionStructureSeeder::class);
        $this->seed(ProductionStructureSeeder::class);

        $this->assertDatabaseCount('operation_types', 8);
        $this->assertDatabaseCount('boms', 1);
        $this->assertDatabaseCount('bom_items', 4);
        $this->assertDatabaseCount('operation_sequences', 1);
        $this->assertDatabaseCount('operation_sequence_steps', 5);

        $this->assertDatabaseHas('operation_types', [
            'code' => OperationTypeCode::QUALITY_CHECK->value,
        ]);

        $this->assertDatabaseHas('operation_sequence_steps', [
            'step_order' => 4,
            'requires_quality_check' => true,
        ]);
    }
}
