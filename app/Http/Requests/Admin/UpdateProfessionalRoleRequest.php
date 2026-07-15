<?php

namespace App\Http\Requests\Admin;

use App\Models\ProfessionalRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: szakmai szerepkör módosításához.
 */
class UpdateProfessionalRoleRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: szakmai szerepkör módosításához.
     *
     * A Laravel Gate-en keresztül a ProfessionalRolePolicy `update` képességét
     * ellenőrzi a `professionalRole` route-paraméterből feloldott modellen.
     */
    public function authorize(): bool
    {
        $professionalRole = $this->route('professionalRole');

        if (! $professionalRole instanceof ProfessionalRole) {
            return false;
        }

        return $this->user()->can('update', $professionalRole);
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: szakmai szerepkör módosításához.
     *
     * @return array<string, ValidationRule|\Illuminate\Contracts\Validation\Rule|array<int, ValidationRule|\Illuminate\Contracts\Validation\Rule|string>|string>
     */
    public function rules(): array
    {
        $professionalRole = $this->route('professionalRole');

        if (! $professionalRole instanceof ProfessionalRole) {
            throw new \LogicException('The professionalRole route parameter must resolve to a ProfessionalRole model.');
        }

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('professional_roles', 'code')->ignore($professionalRole)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
