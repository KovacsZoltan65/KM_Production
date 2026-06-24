<?php

namespace App\Http\Requests\Admin;

use App\Models\PurchaseRequisition;
use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequisitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', PurchaseRequisition::class);
    }

    public function rules(): array
    {
        return [
            'requisition_number' => ['nullable', 'string', 'max:255', 'unique:purchase_requisitions,requisition_number'],
            'requested_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit' => ['required', 'string', 'max:50'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
