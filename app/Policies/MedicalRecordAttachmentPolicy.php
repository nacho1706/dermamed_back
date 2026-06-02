<?php

namespace App\Policies;

use App\Models\MedicalRecord;
use App\Models\MedicalRecordAttachment;
use App\Models\User;

class MedicalRecordAttachmentPolicy
{
    /**
     * Reading attachments is allowed for any authenticated staff role:
     * doctor (medical context), receptionist (needs to attach receipt to billing),
     * clinic_manager (administrative oversight). Patient-facing roles do not exist.
     */
    public function view(User $user, MedicalRecordAttachment $attachment): bool
    {
        return $user->hasAnyRole(['doctor', 'receptionist', 'clinic_manager']);
    }

    /**
     * Only the doctor who owns the record can upload attachments for it.
     */
    public function create(User $user, MedicalRecord $record): bool
    {
        return $user->isDoctor() && $record->doctor_id === $user->id;
    }

    /**
     * Only the owning doctor can delete an attachment.
     */
    public function delete(User $user, MedicalRecordAttachment $attachment): bool
    {
        if (! $user->isDoctor()) {
            return false;
        }

        return $attachment->medicalRecord?->doctor_id === $user->id;
    }
}
