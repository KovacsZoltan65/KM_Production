<?php

namespace App\Http\Requests\Admin;

use App\Enums\OperationTypeCode;
use App\Models\OperationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOperationTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('operationType'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var OperationType $operationType */
        $operationType = $this->route('operationType');

        return [
            'code' => ['required', Rule::enum(OperationTypeCode::class), Rule::unique('operation_types', 'code')->ignore($operationType)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
