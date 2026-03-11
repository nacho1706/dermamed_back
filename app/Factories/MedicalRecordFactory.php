<?php

namespace App\Factories;

use App\Models\MedicalRecord;

class MedicalRecordFactory
{
    public static function fromRequest($request, ?MedicalRecord $medicalRecord = null): MedicalRecord
    {
        $medicalRecord = $medicalRecord ?? new MedicalRecord;
        $medicalRecord->patient_id     = isset($request['patient_id'])     ? $request['patient_id']     : $medicalRecord->patient_id;
        $medicalRecord->doctor_id      = isset($request['doctor_id'])      ? $request['doctor_id']      : $medicalRecord->doctor_id;
        $medicalRecord->appointment_id = isset($request['appointment_id']) ? $request['appointment_id'] : $medicalRecord->appointment_id;
        $medicalRecord->date           = isset($request['date'])           ? $request['date']           : $medicalRecord->date;
        $medicalRecord->content        = isset($request['content'])        ? $request['content']        : $medicalRecord->content;
        $medicalRecord->supplies_used  = isset($request['supplies_used'])  ? $request['supplies_used']  : $medicalRecord->supplies_used;

        return $medicalRecord;
    }
}
