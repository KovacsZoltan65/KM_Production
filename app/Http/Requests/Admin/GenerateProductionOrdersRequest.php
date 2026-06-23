<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class GenerateProductionOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('generateProductionOrders', $this->route('productionPlan'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
