<?php

namespace App\Http\Requests\Admin;

use App\Models\ProfessionalRole;
use Illuminate\Foundation\Http\FormRequest;

class StoreProfessionalRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ProfessionalRole::class);
    }

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
