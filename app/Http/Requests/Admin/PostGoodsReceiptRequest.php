<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A következő üzleti művelethez kapcsolódó HTTP-kérést kezeli: áruátvétel könyveléséhez.
 */
class PostGoodsReceiptRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a következő művelet kérésére: áruátvétel könyveléséhez.
     *
     * A Laravel Gate-en keresztül a `post` képességet ellenőrzi a `goodsReceipt` route-paraméter értékén.
     */
    public function authorize(): bool
    {
        return $this->user()->can('post', $this->route('goodsReceipt'));
    }

    /**
     * Visszaadja a következő művelet bemeneti adatainak validációs szabályait: áruátvétel könyveléséhez.
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
