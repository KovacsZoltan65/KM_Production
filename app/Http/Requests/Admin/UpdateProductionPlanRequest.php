<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('productionPlan'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'planned_start_date' => ['nullable', 'date'],
            'planned_finish_date' => ['nullable', 'date', 'after_or_equal:planned_start_date'],
            'notes' => ['nullable', 'string'],
            'items' => ['array'],
            'items.*.id' => ['required', 'integer', 'exists:production_plan_items,id'],
            'items.*.bom_id' => ['nullable', 'integer', 'exists:boms,id'],
            'items.*.operation_sequence_id' => ['nullable', 'integer', 'exists:operation_sequences,id'],
            'items.*.planned_start_date' => ['nullable', 'date'],
            'items.*.planned_finish_date' => ['nullable', 'date', 'after_or_equal:items.*.planned_start_date'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
