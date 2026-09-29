<?php

namespace App\Support\Procurement;

use Illuminate\Validation\ValidationException;

/** Exact parser shared with replenishment; preserves its precision and overflow rules. */
final class ProcurementDecimal
{
    public static function toScaledInteger(string $value, int $scale, string $field): int
    {
        $value = trim($value);
        if (! preg_match('/^(-?)(\d+)(?:\.(\d+))?$/', $value, $matches)) {
            self::fail($field, 'procurement.replenishment.validation.invalid_quantity');
        }

        $fraction = $matches[3] ?? '';
        if (strlen(ltrim($matches[2], '0')) > 18 - $scale) {
            self::fail($field, 'procurement.replenishment.validation.invalid_result');
        }
        if (strlen($fraction) > $scale && trim(substr($fraction, $scale), '0') !== '') {
            self::fail($field, 'procurement.replenishment.validation.invalid_precision');
        }

        $factor = 10 ** $scale;
        $whole = (int) $matches[2];
        $scaled = ($whole * $factor) + (int) str_pad(substr($fraction, 0, $scale), $scale, '0');

        return $matches[1] === '-' ? -$scaled : $scaled;
    }

    public static function fromThousandths(int $quantity): string
    {
        return sprintf('%d.%03d', intdiv($quantity, 1000), $quantity % 1000);
    }

    private static function fail(string $field, string $translationKey): never
    {
        throw ValidationException::withMessages([$field => __($translationKey)]);
    }
}
