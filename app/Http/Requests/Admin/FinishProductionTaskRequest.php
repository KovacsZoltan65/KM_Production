<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: termelési feladat befejezéséhez.
 */
class FinishProductionTaskRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: termelési feladat befejezéséhez.
     *
     * A Laravel Gate-en keresztül a `finish` képességet ellenőrzi a `productionTask` route-paraméter értékén.
     */
    public function authorize(): bool
    {
        return $this->user()->can('finish', $this->route('productionTask'));
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: termelési feladat befejezéséhez.
     *
     * @return array<string, ValidationRule|Rule|array<int, ValidationRule|Rule|string>|string>
     */
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string'],
        ];
    }
}
