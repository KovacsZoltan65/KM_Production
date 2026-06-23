<?php

namespace App\Http\Requests\Admin;

use App\Models\OperationSequence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOperationSequenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', OperationSequence::class);
    }

    /**
     * @return array<string, mixed>
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
