<?php

namespace App\Factories;

use App\Models\Patient;

class PatientFactory
{
    public static function fromRequest($request, ?Patient $patient = null): Patient
    {
        $patient = $patient ?? new Patient();

        $fields = [
            'first_name',
            'last_name',
            'dni',
            'cuit',
            'email',
            'phone',
            'birth_date',
            'street',
            'street_number',
            'floor',
            'apartment',
            'city',
            'province',
            'zip_code',
            'country',
            'insurance_provider',
        ];

        foreach ($fields as $field) {
            if (isset($request[$field])) {
                $patient->$field = $request[$field];
            }
        }

        return $patient;
    }
}
