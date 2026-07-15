<?php

namespace App\Http\Requests\Admin;

use App\Enums\QualityCheckResult;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: minőségellenőrzés létrehozásához.
 */
class StoreQualityCheckRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: minőségellenőrzés létrehozásához.
     *
     * A Laravel Gate-en keresztül a `check` képességet ellenőrzi a `productionTask` route-paraméter értékén.
     */
    public function authorize(): bool
    {
        return $this->user()->can('check', $this->route('productionTask'));
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: minőségellenőrzés létrehozásához.
     *
     * @return array<string, ValidationRule|\Illuminate\Contracts\Validation\Rule|array<int, ValidationRule|\Illuminate\Contracts\Validation\Rule|string>|string>
     */
    public function rules(): array
    {
        return [
            'checked_by' => ['required', 'integer', 'exists:employees,id'],
            'result' => ['required', Rule::enum(QualityCheckResult::class)],
            'notes' => ['nullable', 'string'],
        ];
    }
}
