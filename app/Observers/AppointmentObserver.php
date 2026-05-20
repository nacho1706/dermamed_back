<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Services\TreatmentReminderService;

class AppointmentObserver
{
    public function __construct(private TreatmentReminderService $reminders) {}

    public function updated(Appointment $appointment): void
    {
        // Solo creamos reminder cuando la transición fue HACIA 'completed'.
        if (
            $appointment->wasChanged('status')
            && $appointment->status === 'completed'
            && $appointment->getOriginal('status') !== 'completed'
        ) {
            // Si el frontend mandó override (días o notes), debería pasarse
            // por una ruta dedicada o vía request en el controller. El observer
            // crea solo el default.
            $this->reminders->createFromAppointment($appointment);
        }
    }
}
