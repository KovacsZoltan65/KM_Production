<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: dokumentum módosításához.
 */
class UpdateDocumentRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: dokumentum módosításához.
     *
     * A Laravel Gate-en keresztül a `update` képességet ellenőrzi a `document` route-paraméter értékén.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('document'));
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: dokumentum módosításához.
     *
     * @return array<string, ValidationRule|Rule|array<int, ValidationRule|Rule|string>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
