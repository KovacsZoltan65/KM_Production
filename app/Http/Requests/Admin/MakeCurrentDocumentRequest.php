<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MakeCurrentDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('makeCurrent', $this->route('document'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
