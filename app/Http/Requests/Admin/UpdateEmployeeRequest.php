<?php

namespace App\Http\Requests\Admin;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('employee'));
    }

    /**
     * @return array<string, mixed>
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
