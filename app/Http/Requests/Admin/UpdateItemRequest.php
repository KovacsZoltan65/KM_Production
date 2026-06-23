<?php

namespace App\Http\Requests\Admin;

use App\Enums\ItemType;
use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('item'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Item $item */
        $item = $this->route('item');

        return [
            'item_number' => ['required', 'string', 'max:255', Rule::unique('items', 'item_number')->ignore($item)],
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
