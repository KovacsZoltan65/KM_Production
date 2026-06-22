<?php

namespace App\Http\Requests\Admin;

use App\Models\FactoryUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFactoryUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('factory_unit'));
    }

    public function rules(): array
    {
        /** @var FactoryUnit $factoryUnit */
        $factoryUnit = $this->route('factoryUnit');

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('factory_units', 'code')->ignore($factoryUnit->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'daily_capacity_minutes' => ['nullable', 'integer', 'min:0'],
            'shift_count' => ['required', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ];
    }
}
