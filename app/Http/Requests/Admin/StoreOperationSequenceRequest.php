<?php

namespace App\Http\Requests\Admin;

use App\Models\OperationSequence;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: műveletsor létrehozásához.
 */
class StoreOperationSequenceRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: műveletsor létrehozásához.
     *
     * A Laravel Gate-en keresztül a OperationSequencePolicy `create` képességét ellenőrzi a OperationSequence modellen.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', OperationSequence::class);
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: műveletsor létrehozásához.
     *
     * @return array<string, ValidationRule|\Illuminate\Contracts\Validation\Rule|array<int, ValidationRule|\Illuminate\Contracts\Validation\Rule|string>|string>
     */
    public function rules(): array
    {
        return [
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'version' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('operation_sequences', 'version')->where('item_id', $this->integer('item_id')),
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
