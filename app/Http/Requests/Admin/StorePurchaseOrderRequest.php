<?php

namespace App\Http\Requests\Admin;

use App\Models\PurchaseOrder;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: beszerzési rendelés létrehozásához.
 */
class StorePurchaseOrderRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: beszerzési rendelés létrehozásához.
     *
     * A Laravel Gate-en keresztül a PurchaseOrderPolicy `create` képességét ellenőrzi a PurchaseOrder modellen.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', PurchaseOrder::class);
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: beszerzési rendelés létrehozásához.
     *
     * @return array<string, ValidationRule|Rule|array<int, ValidationRule|Rule|string>|string>
     */
    public function rules(): array
    {
        return [
            'order_number' => ['nullable', 'string', 'max:255', 'unique:purchase_orders,order_number'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'purchase_requisition_id' => ['nullable', 'integer', 'exists:purchase_requisitions,id'],
            'ordered_at' => ['nullable', 'date'],
            'expected_delivery_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_requisition_item_id' => ['nullable', 'integer', 'exists:purchase_requisition_items,id'],
            'items.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'items.*.ordered_quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit' => ['required', 'string', 'max:50'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
