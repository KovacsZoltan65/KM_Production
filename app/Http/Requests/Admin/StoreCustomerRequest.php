<?php

namespace App\Http\Requests\Admin;

use App\Models\Customer;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: vevő létrehozásához.
 */
class StoreCustomerRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: vevő létrehozásához.
     *
     * A Laravel Gate-en keresztül a CustomerPolicy `create` képességét ellenőrzi a Customer modellen.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Customer::class);
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: vevő létrehozásához.
     *
     * @return array<string, ValidationRule|Rule|array<int, ValidationRule|Rule|string>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', 'unique:customers,code'],
            'name' => ['required', 'string', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'billing_address' => ['nullable', 'string'],
            'shipping_address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
