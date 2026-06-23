<?php

namespace App\Http\Requests\Admin;

use App\Models\Bom;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Bom::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'version' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('boms', 'version')->where('item_id', $this->integer('item_id')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'items' => ['array'],
            'items.*.item_id' => ['required', 'integer', 'exists:items,id', 'distinct'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit' => ['required', 'string', 'max:50'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
