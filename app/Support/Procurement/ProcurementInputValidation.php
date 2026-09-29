<?php

namespace App\Support\Procurement;

use DateTimeImmutable;
use Illuminate\Validation\ValidationException;

final class ProcurementInputValidation
{
    public static function date(string $value, string $field): void
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (! preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $value)
            || $date === false || $date->format('Y-m-d') !== $value || substr($value, 0, 4) === '0000') {
            self::fail($field);
        }
    }

    public static function timestamp(string $value, string $field): void
    {
        if (! preg_match('/^([0-9]{4}-[0-9]{2}-[0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})(?:\.[0-9]{1,6})?(Z|[+-]([0-9]{2}):([0-9]{2}))$/D', $value, $parts)) {
            self::fail($field);
        }
        self::date($parts[1], $field);
        if ((int) $parts[2] > 23 || (int) $parts[3] > 59 || (int) $parts[4] > 59
            || (isset($parts[6]) && ((int) $parts[6] > 23 || (int) $parts[7] > 59))) {
            self::fail($field);
        }
    }

    public static function fail(string $field): never
    {
        throw ValidationException::withMessages([$field => __('procurement.supplier_options.validation.invalid_input', ['attribute' => $field])]);
    }
}
