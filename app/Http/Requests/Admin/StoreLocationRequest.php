<?php

namespace App\Http\Requests\Admin;

use App\Enums\LocationType;
use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Location::class);
    }

    public function rules(): array
    {
        return [
            'factory_unit_id' => ['nullable', 'exists:factory_units,id'],
            'code' => ['required', 'string', 'max:50', 'unique:locations,code'],
            'name' => ['required', 'string', 'max:255'],
            'location_type' => ['required', Rule::enum(LocationType::class)],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
