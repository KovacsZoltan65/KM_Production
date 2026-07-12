<?php

namespace App\Services\Admin;

use App\Models\CapacityReservation;
use App\Models\FactoryUnitCalendar;
use App\Repositories\Contracts\CapacityRepositoryInterface;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * Szabad gyáregységi kapacitásablakot keres a munkanaptár és foglalások alapján.
 *
 * A lekérdezéseket példányon belül gyorsítótárazza; foglalást nem hoz létre.
 */
class CapacitySlotFinder
{
    /**
     * @var array<string, FactoryUnitCalendar|null>
     */
    private array $calendarCache = [];

    /**
     * @var array<int, array{from: CarbonImmutable, until: CarbonImmutable,
     *     reservations: EloquentCollection}>
     */
    private array $reservationCache = [];

    public function __construct(private readonly CapacityRepositoryInterface $capacity) {}

    /**
     * Megkeresi az első ütközésmentes munkaidőablakot a tervezési horizonton.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     *
     * @throws \RuntimeException Ha 180 napon belül nem található szabad időablak.
     */
    public function findSlot(
        int $factoryUnitId,
        CarbonInterface $after,
        int $durationMinutes,
        ?int $ignoreProductionTaskId = null
    ): array {
        $candidate = CarbonImmutable::parse($after);

        for ($attempt = 0; $attempt < 180; $attempt++) {
            $calendar = $this->factoryCalendarFor($factoryUnitId, $candidate->dayOfWeekIso);
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

            $overlap = $this->nextFactoryReservationOverlap(
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

    /**
     * Gyorsítótárazva lekéri a gyáregység adott hétköznapra érvényes naptárát.
     */
    private function factoryCalendarFor(int $factoryUnitId, int $weekday): ?FactoryUnitCalendar
    {
        $key = "{$factoryUnitId}:{$weekday}";

        if (! array_key_exists($key, $this->calendarCache)) {
            $this->calendarCache[$key] = $this->capacity->factoryCalendarFor($factoryUnitId, $weekday);
        }

        return $this->calendarCache[$key];
    }

    /**
     * Visszaadja az időablakkal ütköző első, figyelembe veendő foglalást.
     */
    private function nextFactoryReservationOverlap(
        int $factoryUnitId,
        CarbonInterface $from,
        CarbonInterface $until,
        ?int $ignoreProductionTaskId = null
    ): ?CapacityReservation {
        return $this->factoryReservationsFor($factoryUnitId, $from, $until)
            ->first(
                fn ($reservation): bool => $reservation->production_task_id !== $ignoreProductionTaskId
                    && $reservation->reserved_from->lessThan($until)
                    && $reservation->reserved_until->greaterThan($from)
            );
    }

    /**
     * Visszaadja a gyáregység gyorsítótárazott foglalásait a tervezési horizontra.
     *
     * @return EloquentCollection A kapacitásfoglalások.
     */
    private function factoryReservationsFor(int $factoryUnitId, CarbonInterface $from, CarbonInterface $until): EloquentCollection
    {
        $from = CarbonImmutable::parse($from);
        $until = CarbonImmutable::parse($until);
        $cached = $this->reservationCache[$factoryUnitId] ?? null;

        if (
            $cached === null
            || $from->lessThan($cached['from'])
            || $until->greaterThan($cached['until'])
        ) {
            $horizonFrom = $from->startOfDay();
            $horizonUntil = $from->addDays(180)->endOfDay();

            $this->reservationCache[$factoryUnitId] = [
                'from' => $horizonFrom,
                'until' => $horizonUntil,
                'reservations' => $this->capacity->factoryReservationsForHorizon($factoryUnitId, $horizonFrom, $horizonUntil),
            ];
        }

        return $this->reservationCache[$factoryUnitId]['reservations'];
    }

    /**
     * Megállapítja, hogy a naptárbejegyzés munkanapot jelöl-e.
     */
    private function isWorkingCalendar(?FactoryUnitCalendar $calendar): bool
    {
        return $calendar !== null && $calendar->is_working_day;
    }
}
