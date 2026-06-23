<?php

namespace App\Http\Requests\Admin;

use App\Models\ProductionPlan;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ProductionPlan::class);
    }

    /**
     * @return array<string, mixed>
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
