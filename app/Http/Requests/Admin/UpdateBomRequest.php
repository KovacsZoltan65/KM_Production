<?php

namespace App\Http\Requests\Admin;

use App\Models\Bom;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('bom'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Bom $bom */
        $bom = $this->route('bom');

        return [
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'version' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('boms', 'version')->where('item_id', $this->integer('item_id'))->ignore($bom),
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
