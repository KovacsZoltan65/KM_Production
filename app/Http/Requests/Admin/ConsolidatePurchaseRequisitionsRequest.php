<?php

namespace App\Http\Requests\Admin;

use App\Models\PurchaseRequisition;
use Illuminate\Foundation\Http\FormRequest;

class ConsolidatePurchaseRequisitionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', PurchaseRequisition::class);
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'proposal_ids' => ['required', 'array', 'min:1'],
            'proposal_ids.*' => ['required', 'integer', 'distinct', 'exists:supply_proposals,id'],
        ];
    }
}
