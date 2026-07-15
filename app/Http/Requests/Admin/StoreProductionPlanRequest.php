<?php

namespace App\Http\Requests\Admin;

use App\Models\ProductionPlan;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: termelési terv létrehozásához.
 */
class StoreProductionPlanRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: termelési terv létrehozásához.
     *
     * A Laravel Gate-en keresztül a ProductionPlanPolicy `create` képességét ellenőrzi a ProductionPlan modellen.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', ProductionPlan::class);
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: termelési terv létrehozásához.
     *
     * @return array<string, ValidationRule|Rule|array<int, ValidationRule|Rule|string>|string>
     */
    public function rules(): array
    {
        return [
            'customer_order_id' => ['required', 'integer', 'exists:customer_orders,id'],
            'planned_start_date' => ['nullable', 'date'],
            'planned_finish_date' => ['nullable', 'date', 'after_or_equal:planned_start_date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
