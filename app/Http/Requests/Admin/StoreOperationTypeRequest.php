<?php

namespace App\Http\Requests\Admin;

use App\Enums\OperationTypeCode;
use App\Models\OperationType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: művelettípus létrehozásához.
 */
class StoreOperationTypeRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: művelettípus létrehozásához.
     *
     * A Laravel Gate-en keresztül a OperationTypePolicy `create` képességét ellenőrzi a OperationType modellen.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', OperationType::class);
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: művelettípus létrehozásához.
     *
     * @return array<string, ValidationRule|\Illuminate\Contracts\Validation\Rule|array<int, ValidationRule|\Illuminate\Contracts\Validation\Rule|string>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', Rule::enum(OperationTypeCode::class), 'unique:operation_types,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
