<?php

namespace App\Enums;

/** Persisted values supported by the initial RMA schema. */
enum WarrantyDurationUnit: string
{
    case Day = 'day';
    case Month = 'month';
    case Year = 'year';
}
