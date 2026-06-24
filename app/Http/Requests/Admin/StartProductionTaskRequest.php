<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StartProductionTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('start', $this->route('productionTask'));
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string'],
        ];
    }
}
