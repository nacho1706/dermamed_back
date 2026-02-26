<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $patient = Patient::first();
        $doctor = User::whereHas('roles', function ($q) {
            $q->where('name', 'doctor');
        })->first();
        $service = Service::first();

        if (! $patient || ! $doctor || ! $service) {
            return;
        }

        // 1. Past Appointment (Yesterday)
        Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'service_id' => $service->id,
            'scheduled_start_at' => Carbon::yesterday()->setHour(10),
            'scheduled_end_at' => Carbon::yesterday()->setHour(11),
            'status' => 'completed',
            'reserve_channel' => 'manual',
            'notes' => 'Turno pasado para prueba de congelamiento',
            'check_in_at' => Carbon::yesterday()->setHour(9, 55),
            'real_start_at' => Carbon::yesterday()->setHour(10, 5),
            'real_end_at' => Carbon::yesterday()->setHour(10, 55),
        ]);

        // 2. Future Appointment (Tomorrow)
        Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'service_id' => $service->id,
            'scheduled_start_at' => Carbon::tomorrow()->setHour(10),
            'scheduled_end_at' => Carbon::tomorrow()->setHour(11),
            'status' => 'scheduled',
            'reserve_channel' => 'whatsapp',
            'notes' => 'Turno futuro para prueba de estados',
        ]);
    }
}
