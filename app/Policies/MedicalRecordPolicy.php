<?php

namespace App\Policies;

use App\Models\MedicalRecord;
use App\Models\User;

class MedicalRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isDoctor() || $user->isClinicManager();
    }

    public function view(User $user, MedicalRecord $record): bool
    {
        if ($user->isClinicManager()) {
            return true;
        }

        return $user->isDoctor() && $record->doctor_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isDoctor();
    }

    public function update(User $user, MedicalRecord $record): bool
    {
        return $user->isDoctor() && $record->doctor_id === $user->id;
    }

    public function delete(User $user, MedicalRecord $record): bool
    {
        return $user->isDoctor() && $record->doctor_id === $user->id;
    }
}
