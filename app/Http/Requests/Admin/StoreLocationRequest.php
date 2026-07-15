<?php

namespace App\Http\Requests\Admin;

use App\Enums\LocationType;
use App\Models\Location;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: hely létrehozásához.
 */
class StoreLocationRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: hely létrehozásához.
     *
     * A Laravel Gate-en keresztül a LocationPolicy `create` képességét ellenőrzi a Location modellen.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Location::class);
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: hely létrehozásához.
     *
     * @return array<string, ValidationRule|\Illuminate\Contracts\Validation\Rule|array<int, ValidationRule|\Illuminate\Contracts\Validation\Rule|string>|string>
     */
    public function rules(): array
    {
        return [
            'factory_unit_id' => ['nullable', 'exists:factory_units,id'],
            'code' => ['required', 'string', 'max:50', 'unique:locations,code'],
            'name' => ['required', 'string', 'max:255'],
            'location_type' => ['required', Rule::enum(LocationType::class)],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
