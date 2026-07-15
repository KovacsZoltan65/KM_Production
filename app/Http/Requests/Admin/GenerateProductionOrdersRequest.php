<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: production orders generálásához.
 */
class GenerateProductionOrdersRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: production orders generálásához.
     *
     * A Laravel Gate-en keresztül a `generateProductionOrders` képességet ellenőrzi a `productionPlan` route-paraméter értékén.
     */
    public function authorize(): bool
    {
        return $this->user()->can('generateProductionOrders', $this->route('productionPlan'));
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: production orders generálásához.
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
