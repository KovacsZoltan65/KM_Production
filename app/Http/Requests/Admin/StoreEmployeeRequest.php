<?php

namespace App\Http\Requests\Admin;

use App\Models\Employee;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: alkalmazott létrehozásához.
 */
class StoreEmployeeRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: alkalmazott létrehozásához.
     *
     * A Laravel Gate-en keresztül a EmployeePolicy `create` képességét ellenőrzi a Employee modellen.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Employee::class);
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: alkalmazott létrehozásához.
     *
     * @return array<string, ValidationRule|Rule|array<int, ValidationRule|Rule|string>|string>
     */
    public function rules(): array
    {
        return [
            'employee_number' => ['required', 'string', 'max:255', 'unique:employees,employee_number'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'professional_role_id' => ['nullable', 'exists:professional_roles,id'],
            'user_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['boolean'],
            'hired_at' => ['nullable', 'date'],
            'left_at' => ['nullable', 'date', 'after_or_equal:hired_at'],
        ];
    }
}
