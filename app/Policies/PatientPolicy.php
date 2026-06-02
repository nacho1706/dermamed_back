<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['clinic_manager', 'doctor', 'receptionist']);
    }

    public function view(User $user, Patient $patient): bool
    {
        return $user->hasAnyRole(['clinic_manager', 'doctor', 'receptionist']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['clinic_manager', 'doctor', 'receptionist']);
    }

    public function update(User $user, Patient $patient): bool
    {
        return $user->hasAnyRole(['clinic_manager', 'doctor', 'receptionist']);
    }

    public function delete(User $user, Patient $patient): bool
    {
        return $user->isClinicManager();
    }
}
