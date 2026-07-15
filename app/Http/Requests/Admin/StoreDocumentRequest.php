<?php

namespace App\Http\Requests\Admin;

use App\Enums\DocumentType;
use App\Models\Document;
use App\Support\DocumentableRegistry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: dokumentum létrehozásához.
 */
class StoreDocumentRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: dokumentum létrehozásához.
     *
     * A Laravel Gate-en keresztül a DocumentPolicy `create` képességét ellenőrzi a Document modellen.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Document::class);
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: dokumentum létrehozásához.
     *
     * @return array<string, ValidationRule|\Illuminate\Contracts\Validation\Rule|array<int, ValidationRule|\Illuminate\Contracts\Validation\Rule|string>|string>
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
