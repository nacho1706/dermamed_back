<?php

namespace App\Factories;

use App\Models\MedicalRecordAttachment;

class MedicalRecordAttachmentFactory
{
    /**
     * Build a MedicalRecordAttachment from a validated data array.
     * The controller is responsible for populating `medical_record_id`.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromValidated(array $data, ?MedicalRecordAttachment $attachment = null): MedicalRecordAttachment
    {
        $attachment = $attachment ?? new MedicalRecordAttachment;

        $attachment->medical_record_id = $data['medical_record_id'] ?? $attachment->medical_record_id;
        $attachment->path = $data['path'] ?? $attachment->path;
        $attachment->original_name = $data['original_name'] ?? $attachment->original_name;
        $attachment->mime_type = $data['mime_type'] ?? $attachment->mime_type;
        $attachment->size = $data['size'] ?? $attachment->size;

        return $attachment;
    }
}
