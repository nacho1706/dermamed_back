<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendAppointmentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send WhatsApp reminders for appointments scheduled for tomorrow';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tomorrow = Carbon::tomorrow();
        
        $appointments = Appointment::where('status', 'scheduled')
            ->whereDate('start_time', $tomorrow)
            ->with(['patient', 'doctor'])
            ->get();
            
        $this->info("Found {$appointments->count()} appointments for tomorrow.");
        
        foreach ($appointments as $appointment) {
            // Mocking WhatsApp Notification
            $patientName = $appointment->patient->full_name;
            $doctorName = $appointment->doctor->name;
            $time = $appointment->start_time->format('H:i');
            $phone = $appointment->patient->phone;
            
            Log::info("MOCK WHATSAPP REMINDER to {$phone}: Hola {$patientName}, te recordamos tu turno de mañana con el Dr. {$doctorName} a las {$time}.");
            
            $this->comment("Reminder processed for: {$patientName}");
        }
        
        $this->info('Reminders processed (Mocked).');
    }
}
