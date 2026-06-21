<?php

namespace Database\Seeders;

use App\Enums\LocationType;
use App\Models\FactoryUnit;
use App\Models\Location;
use App\Models\ProfessionalRole;
use Illuminate\Database\Seeder;

class ProductionMasterDataSeeder extends Seeder
{
    /**
     * Seed production master data.
     */
    public function run(): void
    {
        foreach ($this->factoryUnits() as $factoryUnit) {
            FactoryUnit::query()->updateOrCreate(
                ['code' => $factoryUnit['code']],
                $factoryUnit,
            );
        }

        foreach ($this->professionalRoles() as $professionalRole) {
            ProfessionalRole::query()->updateOrCreate(
                ['code' => $professionalRole['code']],
                $professionalRole,
            );
        }

        foreach ($this->locations() as $location) {
            $factoryUnitCode = $location['factory_unit_code'];
            unset($location['factory_unit_code']);

            $location['factory_unit_id'] = $factoryUnitCode
                ? FactoryUnit::query()->where('code', $factoryUnitCode)->value('id')
                : null;

            Location::query()->updateOrCreate(
                ['code' => $location['code']],
                $location,
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function factoryUnits(): array
    {
        return [
            ['code' => 'HEG', 'name' => 'Hegesztő műhely', 'is_active' => true],
            ['code' => 'FES', 'name' => 'Festő műhely', 'is_active' => true],
            ['code' => 'SZR', 'name' => 'Szerelde', 'is_active' => true],
            ['code' => 'MEO', 'name' => 'Minőségellenőrzés', 'is_active' => true],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function professionalRoles(): array
    {
        return [
            ['code' => 'WELDER', 'name' => 'Hegesztő', 'is_active' => true],
            ['code' => 'PAINTER', 'name' => 'Festő', 'is_active' => true],
            ['code' => 'ASSEMBLER', 'name' => 'Szerelő', 'is_active' => true],
            ['code' => 'QUALITY_INSPECTOR', 'name' => 'Minőségellenőr', 'is_active' => true],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function locations(): array
    {
        return [
            [
                'code' => 'MAIN-WH',
                'name' => 'Főraktár',
                'location_type' => LocationType::Warehouse->value,
                'factory_unit_code' => null,
                'is_active' => true,
            ],
            [
                'code' => 'HEG-WIP',
                'name' => 'Hegesztő WIP',
                'location_type' => LocationType::Workshop->value,
                'factory_unit_code' => 'HEG',
                'is_active' => true,
            ],
            [
                'code' => 'FES-WIP',
                'name' => 'Festő WIP',
                'location_type' => LocationType::Workshop->value,
                'factory_unit_code' => 'FES',
                'is_active' => true,
            ],
            [
                'code' => 'SZR-WIP',
                'name' => 'Szerelde WIP',
                'location_type' => LocationType::Workshop->value,
                'factory_unit_code' => 'SZR',
                'is_active' => true,
            ],
            [
                'code' => 'MEO-QA',
                'name' => 'Minőségellenőrzési terület',
                'location_type' => LocationType::QualityArea->value,
                'factory_unit_code' => 'MEO',
                'is_active' => true,
            ],
            [
                'code' => 'FG-WH',
                'name' => 'Készáru raktár',
                'location_type' => LocationType::FinishedGoods->value,
                'factory_unit_code' => null,
                'is_active' => true,
            ],
            [
                'code' => 'SCRAP',
                'name' => 'Selejt terület',
                'location_type' => LocationType::Scrap->value,
                'factory_unit_code' => null,
                'is_active' => true,
            ],
        ];
    }
}
