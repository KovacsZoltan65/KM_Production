<?php

namespace App\Http\Requests\Admin;

use App\Models\Supplier;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: beszállító létrehozásához.
 */
class StoreSupplierRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: beszállító létrehozásához.
     *
     * A Laravel Gate-en keresztül a SupplierPolicy `create` képességét ellenőrzi a Supplier modellen.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Supplier::class);
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: beszállító létrehozásához.
     *
     * @return array<string, ValidationRule|Rule|array<int, ValidationRule|Rule|string>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', 'unique:suppliers,code'],
            'name' => ['required', 'string', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
