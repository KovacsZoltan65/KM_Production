<?php

namespace App\Http\Requests\Admin;

use App\Models\ItemSupplier;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreItemSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ItemSupplier::class);
    }

    /**
     * @return array<string, ValidationRule|\Illuminate\Contracts\Validation\Rule|array<int, ValidationRule|\Illuminate\Contracts\Validation\Rule|string>|string>
     */
    public function rules(): array
    {
        return [
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'supplier_id' => [
                'required',
                'integer',
                'exists:suppliers,id',
                Rule::unique('item_suppliers', 'supplier_id')
                    ->where(fn ($query) => $query->where('item_id', $this->integer('item_id'))),
            ],
            'supplier_item_code' => ['nullable', 'string', 'max:255'],
            'purchase_unit' => ['required', 'string', 'max:50'],
            'conversion_factor' => ['required', 'numeric', 'gt:0'],
            'minimum_order_quantity' => ['nullable', 'numeric', 'min:0'],
            'order_multiple' => ['nullable', 'numeric', 'gt:0'],
            'unit_price' => ['nullable', 'numeric', 'min:0', 'decimal:0,4', 'required_with:currency'],
            'currency' => ['nullable', 'string', 'size:3', 'regex:/^[A-Z]{3}$/', 'required_with:unit_price'],
            'lead_time_days' => ['nullable', 'integer', 'min:0', 'max:36500'],
            'priority' => ['required', 'integer', 'min:1', 'max:9999'],
            'is_preferred' => ['required', 'boolean'],
            'is_approved' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('currency')) {
            $this->merge(['currency' => strtoupper((string) $this->input('currency'))]);
        }
    }
}
