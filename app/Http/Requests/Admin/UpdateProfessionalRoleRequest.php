<?php

namespace App\Http\Requests\Admin;

use App\Models\ProfessionalRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfessionalRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('professional_role'));
    }

    public function rules(): array
    {
        /** @var ProfessionalRole $professionalRole */
        $professionalRole = $this->route('professionalRole');

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('professional_roles', 'code')->ignore($professionalRole->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
