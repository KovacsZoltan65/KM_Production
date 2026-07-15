<?php

namespace App\Http\Requests\Admin;

use App\Models\Employee;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: alkalmazott módosításához.
 */
class UpdateEmployeeRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: alkalmazott módosításához.
     *
     * A Laravel Gate-en keresztül a `update` képességet ellenőrzi a `employee` route-paraméter értékén.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('employee'));
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: alkalmazott módosításához.
     *
     * @return array<string, ValidationRule|\Illuminate\Contracts\Validation\Rule|array<int, ValidationRule|\Illuminate\Contracts\Validation\Rule|string>|string>
     */
    public function rules(): array
    {
        /** @var Employee $employee */
        $employee = $this->route('employee');

        return [
            'employee_number' => ['required', 'string', 'max:255', Rule::unique('employees', 'employee_number')->ignore($employee->id)],
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
