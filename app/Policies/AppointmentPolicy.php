<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['clinic_manager', 'receptionist', 'doctor']);
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->isClinicManager() || $user->isReceptionist()) {
            return true;
        }

        return $user->isDoctor() && $appointment->doctor_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['receptionist', 'doctor', 'clinic_manager']);
    }

    public function update(User $user, Appointment $appointment): bool
    {
        if ($user->isClinicManager() || $user->isReceptionist()) {
            return true;
        }

        return $user->isDoctor() && $appointment->doctor_id === $user->id;
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        if ($user->isClinicManager() || $user->isReceptionist()) {
            return true;
        }

        return $user->isDoctor() && $appointment->doctor_id === $user->id;
    }
}
