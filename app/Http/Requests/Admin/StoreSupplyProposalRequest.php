<?php

namespace App\Http\Requests\Admin;

use App\Enums\SupplyStrategy;
use App\Models\SupplyProposal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplyProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', SupplyProposal::class);
    }

    public function rules(): array
    {
        return [
            'strategy' => ['required', Rule::enum(SupplyStrategy::class)],
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'proposed_quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,3'],
            'required_at' => ['nullable', 'date'],
            'proposed_supply_at' => ['nullable', 'date'],
            'reason_code' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
