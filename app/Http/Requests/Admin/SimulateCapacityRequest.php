<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: kapacitás szimulációjához.
 */
class SimulateCapacityRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: kapacitás szimulációjához.
     *
     * Közvetlenül a `capacity.view` permission meglétét ellenőrzi a hitelesített felhasználón.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('capacity.view') ?? false;
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: kapacitás szimulációjához.
     *
     * @return array<string, ValidationRule|Rule|array<int, ValidationRule|Rule|string>|string>
     */
    public function rules(): array
    {
        return [
            'customer_order_id' => ['required', 'integer', 'exists:customer_orders,id'],
        ];
    }
}
