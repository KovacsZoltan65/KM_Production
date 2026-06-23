<?php

namespace App\Http\Requests\Admin;

use App\Enums\OperationTypeCode;
use App\Models\OperationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOperationTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', OperationType::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', Rule::enum(OperationTypeCode::class), 'unique:operation_types,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
