<?php

namespace App\Http\Requests\Admin;

use App\Enums\LocationType;
use App\Models\Location;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: hely módosításához.
 */
class UpdateLocationRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: hely módosításához.
     *
     * A Laravel Gate-en keresztül a `update` képességet ellenőrzi a `location` route-paraméter értékén.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('location'));
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: hely módosításához.
     *
     * @return array<string, ValidationRule|\Illuminate\Contracts\Validation\Rule|array<int, ValidationRule|\Illuminate\Contracts\Validation\Rule|string>|string>
     */
    public function rules(): array
    {
        /** @var Location $location */
        $location = $this->route('location');

        return [
            'factory_unit_id' => ['nullable', 'exists:factory_units,id'],
            'code' => ['required', 'string', 'max:50', Rule::unique('locations', 'code')->ignore($location->id)],
            'name' => ['required', 'string', 'max:255'],
            'location_type' => ['required', Rule::enum(LocationType::class)],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
