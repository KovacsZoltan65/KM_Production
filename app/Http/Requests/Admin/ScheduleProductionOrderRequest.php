<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleProductionOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('capacity.plan') ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'production_order_id' => ['required', 'integer', 'exists:production_orders,id'],
            'override' => ['sometimes', 'boolean'],
        ];
    }
}
