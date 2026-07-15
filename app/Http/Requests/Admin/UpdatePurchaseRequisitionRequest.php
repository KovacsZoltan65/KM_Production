<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: beszerzési igény módosításához.
 */
class UpdatePurchaseRequisitionRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: beszerzési igény módosításához.
     *
     * A Laravel Gate-en keresztül a `update` képességet ellenőrzi a `purchaseRequisition` route-paraméter értékén.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('purchaseRequisition'));
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: beszerzési igény módosításához.
     *
     * @return array<string, ValidationRule|Rule|array<int, ValidationRule|Rule|string>|string>
     */
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string'],
        ];
    }
}
