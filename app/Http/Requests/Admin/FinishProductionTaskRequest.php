<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class FinishProductionTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('finish', $this->route('productionTask'));
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string'],
        ];
    }
}
