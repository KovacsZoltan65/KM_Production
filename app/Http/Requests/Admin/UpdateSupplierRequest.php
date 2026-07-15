<?php

namespace App\Http\Requests\Admin;

use App\Models\Supplier;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: beszállító módosításához.
 */
class UpdateSupplierRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: beszállító módosításához.
     *
     * A Laravel Gate-en keresztül a `update` képességet ellenőrzi a `supplier` route-paraméter értékén.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('supplier'));
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: beszállító módosításához.
     *
     * @return array<string, ValidationRule|\Illuminate\Contracts\Validation\Rule|array<int, ValidationRule|\Illuminate\Contracts\Validation\Rule|string>|string>
     */
    public function rules(): array
    {
        /** @var Supplier $supplier */
        $supplier = $this->route('supplier');

        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('suppliers', 'code')->ignore($supplier)],
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
