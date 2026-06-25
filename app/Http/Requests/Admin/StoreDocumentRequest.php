<?php

namespace App\Http\Requests\Admin;

use App\Enums\DocumentType;
use App\Models\Document;
use App\Support\DocumentableRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Document::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:51200'],
            'document_type' => ['required', Rule::enum(DocumentType::class)],
            'documentable_type' => ['required', 'string', Rule::in(DocumentableRegistry::allowedValues())],
            'documentable_id' => ['required', 'integer', 'min:1'],
            'title' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
