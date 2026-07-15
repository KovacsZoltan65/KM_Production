<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: beszerzési rendelés lezárásához.
 */
class ClosePurchaseOrderRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: beszerzési rendelés lezárásához.
     *
     * A Laravel Gate-en keresztül a `close` képességet ellenőrzi a `purchaseOrder` route-paraméter értékén.
     */
    public function authorize(): bool
    {
        return $this->user()->can('close', $this->route('purchaseOrder'));
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: beszerzési rendelés lezárásához.
     *
     * A művelet nem fogad külön bemeneti mezőt.
     *
     * @return array<string, ValidationRule|Rule|array<int, ValidationRule|Rule|string>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
