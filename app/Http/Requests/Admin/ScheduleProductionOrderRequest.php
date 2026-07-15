<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: termelési rendelés ütemezéséhez.
 */
class ScheduleProductionOrderRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: termelési rendelés ütemezéséhez.
     *
     * Közvetlenül a `capacity.plan` permission meglétét ellenőrzi a hitelesített felhasználón.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('capacity.plan') ?? false;
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: termelési rendelés ütemezéséhez.
     *
     * @return array<string, ValidationRule|Rule|array<int, ValidationRule|Rule|string>|string>
     */
    public function rules(): array
    {
        return [
            'production_order_id' => ['required', 'integer', 'exists:production_orders,id'],
            'override' => ['sometimes', 'boolean'],
        ];
    }
}
