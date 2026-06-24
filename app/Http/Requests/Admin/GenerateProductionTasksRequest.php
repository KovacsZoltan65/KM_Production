<?php

namespace App\Http\Requests\Admin;

use App\Models\ProductionTask;
use Illuminate\Foundation\Http\FormRequest;

class GenerateProductionTasksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ProductionTask::class);
    }

    public function rules(): array
    {
        return [
            'production_order_id' => ['required', 'integer', 'exists:production_orders,id'],
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
        ];
    }
}
