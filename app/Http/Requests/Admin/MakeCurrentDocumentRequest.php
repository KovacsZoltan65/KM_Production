<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: dokumentum aktuális verzióvá tételéhez.
 */
class MakeCurrentDocumentRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: dokumentum aktuális verzióvá tételéhez.
     *
     * A Laravel Gate-en keresztül a `makeCurrent` képességet ellenőrzi a `document` route-paraméter értékén.
     */
    public function authorize(): bool
    {
        return $this->user()->can('makeCurrent', $this->route('document'));
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: dokumentum aktuális verzióvá tételéhez.
     *
     * A művelet nem fogad külön bemeneti mezőt.
     *
     * @return array<string, ValidationRule|Rule|array<int, ValidationRule|Rule|string>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
