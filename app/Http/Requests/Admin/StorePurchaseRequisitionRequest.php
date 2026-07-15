<?php

namespace App\Http\Requests\Admin;

use App\Models\PurchaseRequisition;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: beszerzési igény létrehozásához.
 */
class StorePurchaseRequisitionRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: beszerzési igény létrehozásához.
     *
     * A Laravel Gate-en keresztül a PurchaseRequisitionPolicy `create` képességét ellenőrzi a PurchaseRequisition modellen.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', PurchaseRequisition::class);
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: beszerzési igény létrehozásához.
     *
     * @return array<string, ValidationRule|Rule|array<int, ValidationRule|Rule|string>|string>
     */
    public function rules(): array
    {
        return [
            'requisition_number' => ['nullable', 'string', 'max:255', 'unique:purchase_requisitions,requisition_number'],
            'requested_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit' => ['required', 'string', 'max:50'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
