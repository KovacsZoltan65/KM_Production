<?php

namespace App\Support\Procurement;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/** Calendar-day estimate only; never a supplier promise. */
final class ProcurementTiming
{
    public static function expectedDate(CarbonInterface $evaluationDate, ?int $leadTimeDays): ?CarbonImmutable
    {
        return $leadTimeDays === null ? null : CarbonImmutable::instance($evaluationDate)->addDays($leadTimeDays);
    }

    public static function isLate(?CarbonInterface $expectedDate, ?CarbonInterface $requiredDate): ?bool
    {
        return $expectedDate === null || $requiredDate === null ? null : $expectedDate->gt($requiredDate);
    }
}
