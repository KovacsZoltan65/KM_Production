<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SimulateCapacityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('capacity.view') ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'customer_order_id' => ['required', 'integer', 'exists:customer_orders,id'],
        ];
    }
}
