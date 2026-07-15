<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: adminisztrációs lista szűrése, rendezése és lapozása.
 */
class IndexRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: adminisztrációs lista szűrése, rendezése és lapozása.
     *
     * A kéréshez nem végez külön jogosultsági ellenőrzést; minden elérő felhasználót engedélyez.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: adminisztrációs lista szűrése, rendezése és lapozása.
     *
     * @return array<string, ValidationRule|\Illuminate\Contracts\Validation\Rule|array<int, ValidationRule|\Illuminate\Contracts\Validation\Rule|string>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:100'],
            'movement_type' => ['nullable', 'string', 'max:100'],
            'document_type' => ['nullable', 'string', 'max:100'],
            'documentable_type' => ['nullable', 'string', 'max:255'],
            'approved' => ['nullable', Rule::in(['0', '1', 0, 1, true, false, 'true', 'false'])],
            'is_current' => ['nullable', Rule::in(['0', '1', 0, 1, true, false, 'true', 'false'])],
            'item_id' => ['nullable', 'integer', 'exists:items,id'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'production_order_id' => ['nullable', 'integer', 'exists:production_orders,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'required_item_id' => ['nullable', 'integer', 'exists:items,id'],
            'customer_order_id' => ['nullable', 'integer', 'exists:customer_orders,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'sort' => ['nullable', 'string', 'max:100'],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', Rule::in([10, 25, 50, 100])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Visszaadja a lista lekérdezéséhez validált szűrési, rendezési és lapozási paramétereket.
     *
     * @return array{
     *     search?: string|null,
     *     status?: string|null,
     *     movement_type?: string|null,
     *     document_type?: string|null,
     *     documentable_type?: string|null,
     *     approved?: bool|int|string|null,
     *     is_current?: bool|int|string|null,
     *     item_id?: int|null,
     *     employee_id?: int|null,
     *     production_order_id?: int|null,
     *     location_id?: int|null,
     *     required_item_id?: int|null,
     *     customer_order_id?: int|null,
     *     customer_id?: int|null,
     *     supplier_id?: int|null,
     *     date_from?: string|null,
     *     date_to?: string|null,
     *     sort?: string|null,
     *     direction?: 'asc'|'desc'|null,
     *     per_page?: 10|25|50|100|null,
     *     page?: positive-int|null
     * }
     */
    public function filters(): array
    {
        return $this->validated();
    }

    /**
     * Visszaadja a lista oldalankénti elemszámát, hiányzó értéknél tízes alapértelmezéssel.
     *
     * A validáció alapján az érték 10, 25, 50 vagy 100 lehet.
     */
    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? 10);
    }
}
