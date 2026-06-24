<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ClosePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('close', $this->route('purchaseOrder'));
    }

    public function rules(): array
    {
        return [];
    }
}
