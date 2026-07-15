<?php

namespace App\Http\Requests\Admin;

use App\Models\OperationSequence;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: műveletsor módosításához.
 */
class UpdateOperationSequenceRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: műveletsor módosításához.
     *
     * A Laravel Gate-en keresztül a `update` képességet ellenőrzi a `operationSequence` route-paraméter értékén.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('operationSequence'));
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: műveletsor módosításához.
     *
     * @return array<string, ValidationRule|\Illuminate\Contracts\Validation\Rule|array<int, ValidationRule|\Illuminate\Contracts\Validation\Rule|string>|string>
     */
    public function rules(): array
    {
        /** @var OperationSequence $operationSequence */
        $operationSequence = $this->route('operationSequence');

        return [
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'version' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('operation_sequences', 'version')->where('item_id', $this->integer('item_id'))->ignore($operationSequence),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'steps' => ['array'],
            'steps.*.step_order' => ['required', 'integer', 'min:1', 'distinct'],
            'steps.*.operation_type_id' => ['required', 'integer', 'exists:operation_types,id'],
            'steps.*.factory_unit_id' => ['required', 'integer', 'exists:factory_units,id'],
            'steps.*.professional_role_id' => ['required', 'integer', 'exists:professional_roles,id'],
            'steps.*.estimated_duration_minutes' => ['required', 'integer', 'min:1'],
            'steps.*.requires_quality_check' => ['boolean'],
            'steps.*.instructions' => ['nullable', 'string'],
        ];
    }
}
