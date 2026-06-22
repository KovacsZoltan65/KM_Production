<?php

namespace App\Http\Requests\Admin;

use App\Models\FactoryUnit;
use Illuminate\Foundation\Http\FormRequest;

class StoreFactoryUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', FactoryUnit::class);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:factory_units,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'daily_capacity_minutes' => ['nullable', 'integer', 'min:0'],
            'shift_count' => ['required', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ];
    }
}
