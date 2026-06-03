<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use Illuminate\Console\Command;

class MarkNoShowAppointments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:mark-no-show-appointments';

    /**
     * @var string
     */
    protected $description = 'Marks stale appointments as no_show — both `scheduled` past their slot and `in_waiting_room` left lingering.';

    /**
     * Two cleanup passes:
     *
     * 1. `scheduled` appointments whose slot ended more than 30 minutes ago
     *    without the patient ever checking in.
     * 2. `in_waiting_room` appointments where check_in was more than 8 hours
     *    ago — the patient most likely left and the front desk forgot to
     *    update the status. 8h covers the full business day so we never
     *    misfire during normal flow.
     */
    public function handle(): int
    {
        $scheduledCutoff = now()->subMinutes(30);
        $waitingRoomCutoff = now()->subHours(8);

        $scheduledStale = Appointment::query()
            ->where('status', 'scheduled')
            ->where('scheduled_start_at', '<=', $scheduledCutoff)
            ->update(['status' => 'no_show']);

        // For in_waiting_room we fall back to scheduled_start_at when
        // check_in_at is null (defensive, shouldn't happen — the observer
        // sets check_in_at on entering the waiting room).
        $waitingStale = Appointment::query()
            ->where('status', 'in_waiting_room')
            ->where(function ($q) use ($waitingRoomCutoff) {
                $q->where('check_in_at', '<=', $waitingRoomCutoff)
                  ->orWhere(function ($q2) use ($waitingRoomCutoff) {
                      $q2->whereNull('check_in_at')
                          ->where('scheduled_start_at', '<=', $waitingRoomCutoff);
                  });
            })
            ->update(['status' => 'no_show']);

        $this->info("Marked {$scheduledStale} stale `scheduled` appointments as no_show.");
        $this->info("Marked {$waitingStale} stale `in_waiting_room` appointments as no_show.");

        return self::SUCCESS;
    }
}
