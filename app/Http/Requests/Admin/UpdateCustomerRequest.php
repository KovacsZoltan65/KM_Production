<?php

namespace App\Http\Requests\Admin;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('customer'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Customer $customer */
        $customer = $this->route('customer');

        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('customers', 'code')->ignore($customer)],
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
