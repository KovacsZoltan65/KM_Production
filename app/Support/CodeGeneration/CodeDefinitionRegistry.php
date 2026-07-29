<?php

namespace App\Support\CodeGeneration;

use App\Enums\ItemType;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\FactoryUnit;
use App\Models\Item;
use App\Models\Location;
use App\Models\ProfessionalRole;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Központilag tartja nyilván a generálható üzleti kódok definícióit.
 */
final class CodeDefinitionRegistry
{
    /**
     * Feloldja a publikus kódtípust és a típusspecifikus kontextust.
     *
     * @param  array<string, mixed>  $context
     */
    public function resolve(string $type, array $context = []): CodeDefinition
    {
        return match ($type) {
            'factory_unit' => $this->definition($type, FactoryUnit::class, 'factory_units', 'code', 'factory_unit'),
            'employee' => $this->definition($type, Employee::class, 'employees', 'employee_number', 'employee'),
            'location' => $this->definition($type, Location::class, 'locations', 'code', 'location'),
            'professional_role' => $this->definition($type, ProfessionalRole::class, 'professional_roles', 'code', 'professional_role'),
            'item' => $this->itemDefinition($context),
            'customer' => $this->definition($type, Customer::class, 'customers', 'code', 'customer'),
            'supplier' => $this->definition($type, Supplier::class, 'suppliers', 'code', 'supplier'),
            default => throw ValidationException::withMessages([
                'type' => __('code_generation.errors.unsupported_type'),
            ]),
        };
    }

    /**
     * @return array<int, string>
     */
    public function supportedTypes(): array
    {
        return ['factory_unit', 'employee', 'location', 'professional_role', 'item', 'customer', 'supplier'];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function itemDefinition(array $context): CodeDefinition
    {
        $rawType = $context['item_type'] ?? null;

        try {
            $itemType = $rawType instanceof ItemType
                ? $rawType
                : ItemType::from((string) $rawType);
        } catch (\ValueError) {
            throw ValidationException::withMessages([
                'item_type' => __('code_generation.errors.unsupported_item_type'),
            ]);
        }

        $prefixKey = $itemType === ItemType::PurchasedMaterial ? 'material' : 'product';

        return $this->definition('item', Item::class, 'items', 'item_number', $prefixKey);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function definition(
        string $type,
        string $modelClass,
        string $table,
        string $column,
        string $prefixKey,
    ): CodeDefinition {
        return new CodeDefinition($type, $modelClass, $table, $column, $prefixKey, true);
    }
}
