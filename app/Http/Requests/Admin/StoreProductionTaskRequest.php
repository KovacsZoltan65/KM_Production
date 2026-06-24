<?php

namespace App\Http\Requests\Admin;

use App\Models\ProductionTask;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductionTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ProductionTask::class);
    }

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
