<?php

namespace App\Http\Requests\Admin;

use App\Models\GoodsReceipt;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: áruátvétel létrehozásához.
 */
class StoreGoodsReceiptRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: áruátvétel létrehozásához.
     *
     * A Laravel Gate-en keresztül a GoodsReceiptPolicy `create` képességét ellenőrzi a GoodsReceipt modellen.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', GoodsReceipt::class);
    }

    /**
     * A régi `received_quantity` mezőt a kanonikus `quantity` mezőre normalizálja validálás előtt.
     */
    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->map(function (array $item): array {
                if (! \array_key_exists('quantity', $item) && \array_key_exists('received_quantity', $item)) {
                    $item['quantity'] = $item['received_quantity'];
                }

                return $item;
            })
            ->all();

        $this->merge(['items' => $items]);
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: áruátvétel létrehozásához.
     *
     * @return array<string, ValidationRule|Rule|array<int, ValidationRule|Rule|string>|string>
     */
    public function rules(): array
    {
        return [
            'receipt_number' => ['nullable', 'string', 'max:255', 'unique:goods_receipts,receipt_number'],
            'purchase_order_id' => ['nullable', 'integer', 'exists:purchase_orders,id'],
            'received_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['nullable', 'integer', 'exists:purchase_order_items,id'],
            'items.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'items.*.item_batch_id' => ['nullable', 'integer', 'exists:item_batches,id'],
            'items.*.location_id' => ['required', 'integer', 'exists:locations,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.received_quantity' => ['nullable', 'numeric', 'gt:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
