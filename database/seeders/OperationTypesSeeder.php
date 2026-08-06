<?php

namespace Database\Seeders;

use App\Models\OperationType;
use Illuminate\Database\Seeder;

class OperationTypesSeeder extends Seeder
{
    public function run(): void
    {
        $operationTypes = [
            ['code' => 'GYÁRTÁS', 'name' => 'Műanyag feldolgozás', 'description' => 'Alapanyagok feldolgozása műanyag ipari tachnológiával'],
            ['code' => 'VÁGÁS', 'name' => 'Sorjázás', 'description' => 'Elkészült termék sorjázása, ha szükséges'],
            ['code' => 'ÖSSZEÁLLÍTÁS', 'name' => 'Összeszerelés', 'description' => 'Alkatrészek összeszerelése'],
            ['code' => 'MINŐSÉGELLENŐRZÉS', 'name' => 'Minőségellenőrzés', 'description' => 'Termékek minőségi ellenőrzése'],
            ['code' => 'CSOMAGOLÁS', 'name' => 'Csomagolás', 'description' => 'Termékek csomagolása a szállítás előtt'],
            ['code' => 'TÁROLÁS', 'name' => 'Tárolás', 'description' => 'Termékek tárolása a raktárban'],
            ['code' => 'KISZÁLLÍTÁS', 'name' => 'Kiszállítás', 'description' => 'Termékek kiszállítása a megrendelőhöz'],
        ];

        foreach ($operationTypes as $type) {
            OperationType::create($type);
        }
    }
}
