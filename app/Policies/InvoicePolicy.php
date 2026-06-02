<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['clinic_manager', 'receptionist']);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->hasAnyRole(['clinic_manager', 'receptionist']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['clinic_manager', 'receptionist']);
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->isClinicManager();
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->isClinicManager();
    }
}
