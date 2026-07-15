<?php

namespace App\Http\Requests\Admin;

use App\Models\CustomerOrder;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: vevői rendelés létrehozásához.
 */
class StoreCustomerOrderRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: vevői rendelés létrehozásához.
     *
     * A Laravel Gate-en keresztül a CustomerOrderPolicy `create` képességét ellenőrzi a CustomerOrder modellen.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', CustomerOrder::class);
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: vevői rendelés létrehozásához.
     *
     * @return array<string, ValidationRule|Rule|array<int, ValidationRule|Rule|string>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'requested_delivery_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit' => ['required', 'string', 'max:50'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
