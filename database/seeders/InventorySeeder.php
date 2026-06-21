<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\ItemBatch;
use App\Models\Location;
use App\Models\StockBalance;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Seed initial inventory balances.
     */
    public function run(): void
    {
        $mainWarehouse = Location::query()->where('code', 'MAIN-WH')->firstOrFail();
        $batchesByItemNumber = $this->seedBatches();

        foreach ($this->stockBalances() as $balance) {
            $item = Item::query()->where('item_number', $balance['item_number'])->firstOrFail();
            $batch = $balance['batch_number'] ? $batchesByItemNumber[$balance['item_number']] : null;

            StockBalance::query()->updateOrCreate(
                [
                    'item_id' => $item->id,
                    'location_id' => $mainWarehouse->id,
                    'item_batch_id' => $batch?->id,
                ],
                [
                    'quantity' => $balance['quantity'],
                ],
            );
        }
    }

    /**
     * @return array<string, ItemBatch>
     */
    private function seedBatches(): array
    {
        $batches = [];

        foreach ($this->batches() as $batch) {
            $item = Item::query()->where('item_number', $batch['item_number'])->firstOrFail();

            $batches[$batch['item_number']] = ItemBatch::query()->updateOrCreate(
                [
                    'item_id' => $item->id,
                    'batch_number' => $batch['batch_number'],
                ],
                [
                    'received_at' => '2026-06-21',
                    'notes' => 'Nyitó készlet batch.',
                ],
            );
        }

        return $batches;
    }

    /**
     * @return array<int, array{item_number: string, batch_number: string}>
     */
    private function batches(): array
    {
        return [
            ['item_number' => 'SCR-M4X20', 'batch_number' => 'BATCH-2026-0001'],
            ['item_number' => 'NUT-M4', 'batch_number' => 'BATCH-2026-0001'],
            ['item_number' => 'PAINT-BLACK', 'batch_number' => 'BATCH-2026-0001'],
        ];
    }

    /**
     * @return array<int, array{item_number: string, batch_number: string|null, quantity: int}>
     */
    private function stockBalances(): array
    {
        return [
            ['item_number' => 'SCR-M4X20', 'batch_number' => 'BATCH-2026-0001', 'quantity' => 500],
            ['item_number' => 'NUT-M4', 'batch_number' => 'BATCH-2026-0001', 'quantity' => 500],
            ['item_number' => 'PLATE-200-300-4', 'batch_number' => null, 'quantity' => 50],
            ['item_number' => 'PLATE-150-200-4', 'batch_number' => null, 'quantity' => 50],
            ['item_number' => 'PAINT-BLACK', 'batch_number' => 'BATCH-2026-0001', 'quantity' => 25],
        ];
    }
}
