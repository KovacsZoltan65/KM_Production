<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: beszerzési rendelés generálásához.
 */
class GeneratePurchaseOrderRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: beszerzési rendelés generálásához.
     *
     * A Laravel Gate-en keresztül a `generatePurchaseOrder` képességet ellenőrzi a `purchaseRequisition` route-paraméter értékén.
     */
    public function authorize(): bool
    {
        return $this->user()->can('generatePurchaseOrder', $this->route('purchaseRequisition'));
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: beszerzési rendelés generálásához.
     *
     * @return array<string, ValidationRule|Rule|array<int, ValidationRule|Rule|string>|string>
     */
    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'expected_delivery_date' => ['nullable', 'date'],
        ];
    }
}
