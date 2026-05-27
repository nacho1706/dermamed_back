<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\Service;
use App\Models\TreatmentReminder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TreatmentReminderSeeder extends Seeder
{
    public function run(): void
    {
        $patients = Patient::take(5)->get();
        $services = Service::take(3)->get();
        $doctor = User::whereHas('roles', fn ($q) => $q->where('name', 'doctor'))->first();
        $creator = User::whereHas('roles', fn ($q) => $q->where('name', 'clinic_manager'))->first();

        if ($patients->isEmpty() || $services->isEmpty() || ! $doctor || ! $creator) {
            $this->command->warn('TreatmentReminderSeeder: faltan pacientes/servicios/usuarios base.');

            return;
        }

        // 3 activos (notify_from_at <= today, due_at en próximos días)
        foreach (range(0, 2) as $i) {
            TreatmentReminder::create([
                'patient_id' => $patients[$i]->id,
                'service_id' => $services[$i % $services->count()]->id,
                'doctor_id' => $doctor->id,
                'created_by_user_id' => $creator->id,
                'due_at' => Carbon::today()->addDays(($i + 1) * 3),
                'notify_from_at' => Carbon::today()->subDays(2),
                'status' => 'pending',
                'notes' => 'Control post-procedimiento',
            ]);
        }

        // 2 próximos (notify_from_at > today)
        foreach (range(3, 4) as $i) {
            TreatmentReminder::create([
                'patient_id' => $patients[$i]->id,
                'service_id' => $services[$i % $services->count()]->id,
                'doctor_id' => $doctor->id,
                'created_by_user_id' => $creator->id,
                'due_at' => Carbon::today()->addDays(60),
                'notify_from_at' => Carbon::today()->addDays(30),
                'status' => 'pending',
            ]);
        }

        // 2 en historial (uno contacted, uno dismissed)
        TreatmentReminder::create([
            'patient_id' => $patients[0]->id,
            'service_id' => $services[0]->id,
            'doctor_id' => $doctor->id,
            'created_by_user_id' => $creator->id,
            'due_at' => Carbon::today()->subDays(20),
            'notify_from_at' => Carbon::today()->subDays(34),
            'status' => 'contacted',
            'contacted_at' => Carbon::today()->subDays(20),
            'contacted_via' => 'whatsapp',
            'contacted_by_user_id' => $creator->id,
        ]);

        TreatmentReminder::create([
            'patient_id' => $patients[1]->id,
            'service_id' => $services[1]->id,
            'doctor_id' => $doctor->id,
            'created_by_user_id' => $creator->id,
            'due_at' => Carbon::today()->subDays(5),
            'notify_from_at' => Carbon::today()->subDays(19),
            'status' => 'dismissed',
            'dismissed_reason' => 'Paciente avisó que no va a volver',
            'dismissed_by_user_id' => $creator->id,
        ]);
    }
}
