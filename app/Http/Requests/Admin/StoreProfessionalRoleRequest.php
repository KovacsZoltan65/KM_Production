<?php

namespace App\Http\Requests\Admin;

use App\Models\ProfessionalRole;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: szakmai szerepkör létrehozásához.
 */
class StoreProfessionalRoleRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: szakmai szerepkör létrehozásához.
     *
     * A Laravel Gate-en keresztül a ProfessionalRolePolicy `create` képességét ellenőrzi a ProfessionalRole modellen.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', ProfessionalRole::class);
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: szakmai szerepkör létrehozásához.
     *
     * @return array<string, ValidationRule|Rule|array<int, ValidationRule|Rule|string>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:professional_roles,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
