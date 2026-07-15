<?php

namespace App\Http\Requests\Admin;

use App\Models\ProductionTask;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: termelési feladat létrehozásához.
 */
class StoreProductionTaskRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: termelési feladat létrehozásához.
     *
     * A Laravel Gate-en keresztül a ProductionTaskPolicy `create` képességét ellenőrzi a ProductionTask modellen.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', ProductionTask::class);
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: termelési feladat létrehozásához.
     *
     * @return array<string, ValidationRule|Rule|array<int, ValidationRule|Rule|string>|string>
     */
    public function rules(): array
    {
        return [
            'production_order_id' => ['required', 'integer', 'exists:production_orders,id'],
            'item_instance_id' => ['required', 'integer', 'exists:item_instances,id'],
            'operation_sequence_step_id' => ['required', 'integer', 'exists:operation_sequence_steps,id'],
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'status' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
