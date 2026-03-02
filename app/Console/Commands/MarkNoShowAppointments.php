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
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marks scheduled appointments that have passed their start time as no_show.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = Appointment::where('status', 'scheduled')
            ->where('scheduled_start_at', '<=', now())
            ->update(['status' => 'no_show']);

        $this->info("Successfully marked {$count} passed appointments as no_show.");
    }
}
