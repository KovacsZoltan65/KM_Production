<?php

namespace App\Http\Requests\Admin;

use App\Enums\QualityCheckResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQualityCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('check', $this->route('productionTask'));
    }

    public function rules(): array
    {
        return [
            'checked_by' => ['required', 'integer', 'exists:employees,id'],
            'result' => ['required', Rule::enum(QualityCheckResult::class)],
            'notes' => ['nullable', 'string'],
        ];
    }
}
