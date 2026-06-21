<?php

namespace Database\Seeders;

use App\Enums\OperationTypeCode;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\FactoryUnit;
use App\Models\Item;
use App\Models\OperationSequence;
use App\Models\OperationSequenceStep;
use App\Models\OperationType;
use App\Models\ProfessionalRole;
use Illuminate\Database\Seeder;

class ProductionStructureSeeder extends Seeder
{
    /**
     * Seed BOM and operation structure data.
     */
    public function run(): void
    {
        $this->seedOperationTypes();
        $this->seedProductAaaBom();
        $this->seedProductAaaOperationSequence();
    }

    private function seedOperationTypes(): void
    {
        foreach (OperationTypeCode::cases() as $operationTypeCode) {
            OperationType::query()->updateOrCreate(
                ['code' => $operationTypeCode->value],
                [
                    'name' => str($operationTypeCode->value)->replace('_', ' ')->title()->toString(),
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedProductAaaBom(): void
    {
        $product = Item::query()->where('item_number', 'PRODUCT-AAA')->firstOrFail();

        $bom = Bom::query()->updateOrCreate(
            [
                'item_id' => $product->id,
                'version' => 1,
            ],
            [
                'name' => 'PRODUCT-AAA BOM V1',
                'description' => 'Alap AAA végtermék darabjegyzék.',
                'is_active' => true,
            ],
        );

        foreach ($this->productAaaBomItems() as $bomItem) {
            $item = Item::query()->where('item_number', $bomItem['item_number'])->firstOrFail();

            BomItem::query()->updateOrCreate(
                [
                    'bom_id' => $bom->id,
                    'item_id' => $item->id,
                ],
                [
                    'quantity' => $bomItem['quantity'],
                    'unit' => $bomItem['unit'],
                ],
            );
        }
    }

    private function seedProductAaaOperationSequence(): void
    {
        $product = Item::query()->where('item_number', 'PRODUCT-AAA')->firstOrFail();

        $operationSequence = OperationSequence::query()->updateOrCreate(
            [
                'item_id' => $product->id,
                'version' => 1,
            ],
            [
                'name' => 'PRODUCT-AAA műveletsor V1',
                'description' => 'Alap AAA végtermék műveletsor.',
                'is_active' => true,
            ],
        );

        foreach ($this->productAaaOperationSteps() as $step) {
            $operationType = OperationType::query()->where('code', $step['operation_type_code'])->firstOrFail();
            $factoryUnit = FactoryUnit::query()->where('code', $step['factory_unit_code'])->firstOrFail();
            $professionalRole = ProfessionalRole::query()->where('code', $step['professional_role_code'])->firstOrFail();

            OperationSequenceStep::query()->updateOrCreate(
                [
                    'operation_sequence_id' => $operationSequence->id,
                    'step_order' => $step['step_order'],
                ],
                [
                    'operation_type_id' => $operationType->id,
                    'factory_unit_id' => $factoryUnit->id,
                    'professional_role_id' => $professionalRole->id,
                    'estimated_duration_minutes' => $step['estimated_duration_minutes'],
                    'requires_quality_check' => $step['requires_quality_check'],
                    'instructions' => $step['instructions'],
                ],
            );
        }
    }

    /**
     * @return array<int, array{item_number: string, quantity: float|int, unit: string}>
     */
    private function productAaaBomItems(): array
    {
        return [
            ['item_number' => 'COVER-200-300', 'quantity' => 1, 'unit' => 'db'],
            ['item_number' => 'UNIT-BASE', 'quantity' => 1, 'unit' => 'db'],
            ['item_number' => 'SCR-M4X20', 'quantity' => 8, 'unit' => 'db'],
            ['item_number' => 'NUT-M4', 'quantity' => 8, 'unit' => 'db'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function productAaaOperationSteps(): array
    {
        return [
            [
                'step_order' => 1,
                'operation_type_code' => OperationTypeCode::WELDING->value,
                'factory_unit_code' => 'HEG',
                'professional_role_code' => 'WELDER',
                'estimated_duration_minutes' => 90,
                'requires_quality_check' => false,
                'instructions' => 'Alap hegesztési művelet.',
            ],
            [
                'step_order' => 2,
                'operation_type_code' => OperationTypeCode::PAINTING->value,
                'factory_unit_code' => 'FES',
                'professional_role_code' => 'PAINTER',
                'estimated_duration_minutes' => 60,
                'requires_quality_check' => false,
                'instructions' => 'Felület előkészítés és festés.',
            ],
            [
                'step_order' => 3,
                'operation_type_code' => OperationTypeCode::ASSEMBLY->value,
                'factory_unit_code' => 'SZR',
                'professional_role_code' => 'ASSEMBLER',
                'estimated_duration_minutes' => 75,
                'requires_quality_check' => false,
                'instructions' => 'Végszerelés.',
            ],
            [
                'step_order' => 4,
                'operation_type_code' => OperationTypeCode::QUALITY_CHECK->value,
                'factory_unit_code' => 'MEO',
                'professional_role_code' => 'QUALITY_INSPECTOR',
                'estimated_duration_minutes' => 30,
                'requires_quality_check' => true,
                'instructions' => 'Végellenőrzés.',
            ],
            [
                'step_order' => 5,
                'operation_type_code' => OperationTypeCode::PACKAGING->value,
                'factory_unit_code' => 'SZR',
                'professional_role_code' => 'ASSEMBLER',
                'estimated_duration_minutes' => 20,
                'requires_quality_check' => false,
                'instructions' => 'Csomagolás.',
            ],
        ];
    }
}
