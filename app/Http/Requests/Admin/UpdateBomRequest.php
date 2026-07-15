<?php

namespace App\Http\Requests\Admin;

use App\Models\Bom;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: anyagjegyzék módosításához.
 */
class UpdateBomRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: anyagjegyzék módosításához.
     *
     * A Laravel Gate-en keresztül a `update` képességet ellenőrzi a `bom` route-paraméter értékén.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('bom'));
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: anyagjegyzék módosításához.
     *
     * @return array<string, ValidationRule|\Illuminate\Contracts\Validation\Rule|array<int, ValidationRule|\Illuminate\Contracts\Validation\Rule|string>|string>
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
