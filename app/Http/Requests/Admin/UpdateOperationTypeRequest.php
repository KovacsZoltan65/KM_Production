<?php

namespace App\Http\Requests\Admin;

use App\Enums\OperationTypeCode;
use App\Models\OperationType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: művelettípus módosításához.
 */
class UpdateOperationTypeRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: művelettípus módosításához.
     *
     * A Laravel Gate-en keresztül a `update` képességet ellenőrzi a `operationType` route-paraméter értékén.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('operationType'));
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: művelettípus módosításához.
     *
     * @return array<string, ValidationRule|\Illuminate\Contracts\Validation\Rule|array<int, ValidationRule|\Illuminate\Contracts\Validation\Rule|string>|string>
     */
    public function rules(): array
    {
        /** @var OperationType $operationType */
        $operationType = $this->route('operationType');

        return [
            'code' => ['required', Rule::enum(OperationTypeCode::class), Rule::unique('operation_types', 'code')->ignore($operationType)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
