<?php

namespace App\Http\Requests\Admin;

use App\Models\PurchaseRequisition;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SelectPurchaseRequisitionSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        $requisition = $this->route('purchaseRequisition');

        return $requisition instanceof PurchaseRequisition
            && $this->user()->can('update', $requisition);
    }

    /** @return array<string, ValidationRule|Rule|array<int, ValidationRule|Rule|string>|string> */
    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
        ];
    }
}
