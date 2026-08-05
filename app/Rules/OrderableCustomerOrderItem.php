<?php

namespace App\Rules;

use App\Models\Item;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/** Ellenőrzi, hogy a cikk aktív, rendelhető késztermék-e. */
class OrderableCustomerOrderItem implements ValidationRule
{
    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $itemId = filter_var($value, FILTER_VALIDATE_INT);

        if (
            $itemId === false
            || ! Item::query()->orderable()->whereKey($itemId)->exists()
        ) {
            $fail('orders.validation.only_active_finished_products')->translate();
        }
    }
}
