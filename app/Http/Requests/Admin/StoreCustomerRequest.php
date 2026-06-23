<?php

namespace App\Http\Requests\Admin;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Customer::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', 'unique:customers,code'],
            'name' => ['required', 'string', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'billing_address' => ['nullable', 'string'],
            'shipping_address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
