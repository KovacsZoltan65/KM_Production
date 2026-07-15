<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: termelési feladat anyagfelhasználása létrehozásához.
 */
class StoreProductionTaskMaterialRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: termelési feladat anyagfelhasználása létrehozásához.
     *
     * A Laravel Gate-en keresztül a `useMaterials` képességet ellenőrzi a `productionTask` route-paraméter értékén.
     */
    public function authorize(): bool
    {
        return $this->user()->can('useMaterials', $this->route('productionTask'));
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: termelési feladat anyagfelhasználása létrehozásához.
     *
     * @return array<string, ValidationRule|Rule|array<int, ValidationRule|Rule|string>|string>
     */
    public function rules(): array
    {
        return [
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'item_batch_id' => ['nullable', 'integer', 'exists:item_batches,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'stock_reservation_id' => ['nullable', 'integer', 'exists:stock_reservations,id'],
            'planned_quantity' => ['nullable', 'numeric', 'gte:0'],
            'used_quantity' => ['required', 'numeric', 'gt:0'],
            'unit' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
