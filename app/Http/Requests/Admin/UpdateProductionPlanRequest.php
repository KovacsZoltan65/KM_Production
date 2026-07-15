<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: termelési terv módosításához.
 */
class UpdateProductionPlanRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: termelési terv módosításához.
     *
     * A Laravel Gate-en keresztül a `update` képességet ellenőrzi a `productionPlan` route-paraméter értékén.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('productionPlan'));
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: termelési terv módosításához.
     *
     * @return array<string, ValidationRule|Rule|array<int, ValidationRule|Rule|string>|string>
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
