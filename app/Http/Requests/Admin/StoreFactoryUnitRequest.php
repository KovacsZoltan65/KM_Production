<?php

namespace App\Http\Requests\Admin;

use App\Models\FactoryUnit;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: gyáregység létrehozásához.
 */
class StoreFactoryUnitRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: gyáregység létrehozásához.
     *
     * A Laravel Gate-en keresztül a FactoryUnitPolicy `create` képességét ellenőrzi a FactoryUnit modellen.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', FactoryUnit::class);
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: gyáregység létrehozásához.
     *
     * @return array<string, ValidationRule|Rule|array<int, ValidationRule|Rule|string>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:factory_units,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'daily_capacity_minutes' => ['nullable', 'integer', 'min:0'],
            'shift_count' => ['required', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ];
    }
}
