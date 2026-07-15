<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: rendszerszerepkör létrehozásához.
 */
class StoreRoleRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: rendszerszerepkör létrehozásához.
     *
     * Közvetlenül a `roles.create` permission meglétét ellenőrzi a hitelesített felhasználón.
     */
    public function authorize(): bool
    {
        return $this->user()->can('roles.create');
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: rendszerszerepkör létrehozásához.
     *
     * @return array<string, ValidationRule|\Illuminate\Contracts\Validation\Rule|array<int, ValidationRule|\Illuminate\Contracts\Validation\Rule|string>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::exists(Permission::class, 'name')],
        ];
    }
}
