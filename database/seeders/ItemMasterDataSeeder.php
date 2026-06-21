<?php

namespace Database\Seeders;

use App\Enums\ItemType;
use App\Models\Item;
use Illuminate\Database\Seeder;

class ItemMasterDataSeeder extends Seeder
{
    /**
     * Seed item master data.
     */
    public function run(): void
    {
        foreach ($this->items() as $item) {
            Item::query()->updateOrCreate(
                ['item_number' => $item['item_number']],
                $item,
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function items(): array
    {
        return [
            [
                'item_number' => 'SCR-M4X20',
                'name' => 'Csavar M4x20',
                'item_type' => ItemType::PurchasedMaterial->value,
                'unit' => 'db',
                'length' => 20,
                'diameter' => 4,
                'requires_serial_number' => false,
                'is_active' => true,
            ],
            [
                'item_number' => 'NUT-M4',
                'name' => 'Csavar anya M4',
                'item_type' => ItemType::PurchasedMaterial->value,
                'unit' => 'db',
                'diameter' => 4,
                'requires_serial_number' => false,
                'is_active' => true,
            ],
            [
                'item_number' => 'PLATE-200-300-4',
                'name' => 'Fém lemez 200x300x4',
                'item_type' => ItemType::PurchasedMaterial->value,
                'unit' => 'db',
                'width' => 200,
                'length' => 300,
                'thickness' => 4,
                'requires_serial_number' => false,
                'is_active' => true,
            ],
            [
                'item_number' => 'PLATE-150-200-4',
                'name' => 'Fém lemez 150x200x4',
                'item_type' => ItemType::PurchasedMaterial->value,
                'unit' => 'db',
                'width' => 150,
                'length' => 200,
                'thickness' => 4,
                'requires_serial_number' => false,
                'is_active' => true,
            ],
            [
                'item_number' => 'PAINT-BLACK',
                'name' => 'Fekete festék',
                'item_type' => ItemType::PurchasedMaterial->value,
                'unit' => 'liter',
                'requires_serial_number' => false,
                'is_active' => true,
            ],
            [
                'item_number' => 'COVER-200-300',
                'name' => 'Burkolat 200x300',
                'item_type' => ItemType::ManufacturedPart->value,
                'unit' => 'db',
                'width' => 200,
                'length' => 300,
                'requires_serial_number' => true,
                'is_active' => true,
            ],
            [
                'item_number' => 'COVER-150-200',
                'name' => 'Burkolat 150x200',
                'item_type' => ItemType::ManufacturedPart->value,
                'unit' => 'db',
                'width' => 150,
                'length' => 200,
                'requires_serial_number' => true,
                'is_active' => true,
            ],
            [
                'item_number' => 'UNIT-BASE',
                'name' => 'Alap részegység',
                'item_type' => ItemType::SemiFinishedProduct->value,
                'unit' => 'db',
                'requires_serial_number' => true,
                'is_active' => true,
            ],
            [
                'item_number' => 'PRODUCT-AAA',
                'name' => 'AAA végtermék',
                'item_type' => ItemType::FinishedProduct->value,
                'unit' => 'db',
                'requires_serial_number' => true,
                'is_active' => true,
            ],
        ];
    }
}
