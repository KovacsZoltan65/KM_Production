<?php

namespace App\Services\Admin;

use App\Models\FactoryUnitCalendar;
use App\Repositories\Contracts\CapacityRepositoryInterface;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class CapacitySlotFinder
{
    public function __construct(private readonly CapacityRepositoryInterface $capacity) {}

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function findSlot(
        int $factoryUnitId,
        CarbonInterface $after,
        int $durationMinutes,
        ?int $ignoreProductionTaskId = null
    ): array {
        $candidate = CarbonImmutable::parse($after);

        for ($attempt = 0; $attempt < 180; $attempt++) {
            $calendar = $this->capacity->factoryCalendarFor($factoryUnitId, $candidate->dayOfWeekIso);
            if (! $this->isWorkingCalendar($calendar)) {
                $candidate = $candidate->addDay()->startOfDay()->addHours(8);

                continue;
            }

            $workStart = $candidate->setTimeFromTimeString($calendar->work_start);
            $workEnd = $candidate->setTimeFromTimeString($calendar->work_end);
            $candidate = $candidate->lessThan($workStart) ? $workStart : $candidate;

            $reservedUntil = $candidate->addMinutes($durationMinutes);
            if ($reservedUntil->greaterThan($workEnd)) {
                $candidate = $candidate->addDay()->startOfDay()->addHours(8);

                continue;
            }

            $overlap = $this->capacity->nextFactoryReservationOverlap(
                $factoryUnitId,
                $candidate,
                $reservedUntil,
                $ignoreProductionTaskId,
            );

            if ($overlap !== null) {
                $candidate = CarbonImmutable::parse($overlap->reserved_until);

                continue;
            }

            return [$candidate, $reservedUntil];
        }

        throw new \RuntimeException('No available capacity slot was found in the planning horizon.');
    }

    private function isWorkingCalendar(?FactoryUnitCalendar $calendar): bool
    {
        return $calendar !== null && $calendar->is_working_day;
    }
}
