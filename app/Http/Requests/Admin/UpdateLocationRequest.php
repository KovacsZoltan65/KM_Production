<?php

namespace App\Http\Requests\Admin;

use App\Enums\LocationType;
use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('location'));
    }

    public function rules(): array
    {
        /** @var Location $location */
        $location = $this->route('location');

        return [
            'factory_unit_id' => ['nullable', 'exists:factory_units,id'],
            'code' => ['required', 'string', 'max:50', Rule::unique('locations', 'code')->ignore($location->id)],
            'name' => ['required', 'string', 'max:255'],
            'location_type' => ['required', Rule::enum(LocationType::class)],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
