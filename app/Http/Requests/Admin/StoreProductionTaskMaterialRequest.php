<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductionTaskMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('useMaterials', $this->route('productionTask'));
    }

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
