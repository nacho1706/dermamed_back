<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\DoctorAvailability;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

class AvailableSlotsService
{
    /**
     * Compute the list of bookable time slots for a doctor on a given date.
     * Subtracts existing appointments (excluding cancelled / no_show) from
     * the doctor's working windows in increments of $slotMinutes.
     *
     * @return list<array{start: string, end: string}>
     */
    public function forDoctor(int $doctorId, string $date, int $slotMinutes = 30): array
    {
        $day = CarbonImmutable::parse($date);
        // Carbon dayOfWeek: 0 = Sunday … 6 = Saturday. The seed uses 1..7
        // (Mon..Sun) per the spec — normalise to Carbon's scale.
        $dow = (int) $day->dayOfWeek;

        $windows = DoctorAvailability::where('doctor_id', $doctorId)
            ->where('day_of_week', $dow)
            ->orderBy('start_time')
            ->get(['start_time', 'end_time']);

        if ($windows->isEmpty()) {
            return [];
        }

        $startOfDay = $day->startOfDay();
        $endOfDay = $day->endOfDay();

        $busy = Appointment::where('doctor_id', $doctorId)
            ->whereBetween('scheduled_start_at', [$startOfDay, $endOfDay])
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->get(['scheduled_start_at', 'scheduled_end_at'])
            ->map(fn ($a) => [
                'start' => Carbon::parse($a->scheduled_start_at),
                'end' => Carbon::parse($a->scheduled_end_at),
            ])
            ->all();

        $slots = [];
        foreach ($windows as $window) {
            $cursor = $day->setTimeFromTimeString($window->start_time);
            $windowEnd = $day->setTimeFromTimeString($window->end_time);

            while ($cursor->lt($windowEnd)) {
                $slotEnd = $cursor->addMinutes($slotMinutes);
                if ($slotEnd->gt($windowEnd)) {
                    break;
                }

                if (! $this->overlapsBusy($cursor, $slotEnd, $busy)) {
                    $slots[] = [
                        'start' => $cursor->toIso8601String(),
                        'end' => $slotEnd->toIso8601String(),
                    ];
                }

                $cursor = $slotEnd;
            }
        }

        return $slots;
    }

    /**
     * @param  list<array{start: Carbon, end: Carbon}>  $busy
     */
    private function overlapsBusy(CarbonImmutable $start, CarbonImmutable $end, array $busy): bool
    {
        foreach ($busy as $b) {
            // overlap = NOT (slotEnd <= busyStart || slotStart >= busyEnd)
            if (! ($end->lessThanOrEqualTo($b['start']) || $start->greaterThanOrEqualTo($b['end']))) {
                return true;
            }
        }
        return false;
    }
}
