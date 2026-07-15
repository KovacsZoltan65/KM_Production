<?php

namespace App\Http\Requests\Admin;

use App\Models\FactoryUnit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: gyáregység módosításához.
 */
class UpdateFactoryUnitRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: gyáregység módosításához.
     *
     * A Laravel Gate-en keresztül a FactoryUnitPolicy `update` képességét
     * ellenőrzi a `factoryUnit` route-paraméterből feloldott modellen.
     */
    public function authorize(): bool
    {
        $factoryUnit = $this->route('factoryUnit');

        if (! $factoryUnit instanceof FactoryUnit) {
            return false;
        }

        return $this->user()->can('update', $factoryUnit);
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: gyáregység módosításához.
     *
     * @return array<string, ValidationRule|\Illuminate\Contracts\Validation\Rule|array<int, ValidationRule|\Illuminate\Contracts\Validation\Rule|string>|string>
     */
    public function rules(): array
    {
        $factoryUnit = $this->route('factoryUnit');

        if (! $factoryUnit instanceof FactoryUnit) {
            throw new \LogicException('The factoryUnit route parameter must resolve to a FactoryUnit model.');
        }

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('factory_units', 'code')->ignore($factoryUnit)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'daily_capacity_minutes' => ['nullable', 'integer', 'min:0'],
            'shift_count' => ['required', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ];
    }
}
