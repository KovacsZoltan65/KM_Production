<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: vevői rendelés visszavonásához.
 */
class CancelCustomerOrderRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: vevői rendelés visszavonásához.
     *
     * A Laravel Gate-en keresztül a `cancel` képességet ellenőrzi a `customerOrder` route-paraméter értékén.
     */
    public function authorize(): bool
    {
        return $this->user()->can('cancel', $this->route('customerOrder'));
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: vevői rendelés visszavonásához.
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
