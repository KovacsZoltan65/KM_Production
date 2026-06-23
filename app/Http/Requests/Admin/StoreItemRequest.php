<?php

namespace App\Http\Requests\Admin;

use App\Enums\ItemType;
use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Item::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'item_number' => ['required', 'string', 'max:255', 'unique:items,item_number'],
            'name' => ['required', 'string', 'max:255'],
            'item_type' => ['required', Rule::enum(ItemType::class)],
            'unit' => ['required', 'string', 'max:50'],
            'width' => ['nullable', 'numeric', 'min:0'],
            'length' => ['nullable', 'numeric', 'min:0'],
            'thickness' => ['nullable', 'numeric', 'min:0'],
            'diameter' => ['nullable', 'numeric', 'min:0'],
            'requires_serial_number' => ['sometimes', 'boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
