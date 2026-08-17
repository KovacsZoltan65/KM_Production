<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class CalculatePurchaseRequisitionReplenishmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('purchaseRequisition'));
    }

    /** @return array<string, never> */
    public function rules(): array
    {
        return [];
    }
}
