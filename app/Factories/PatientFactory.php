<?php

namespace App\Factories;

use App\Models\Patient;

class PatientFactory
{
    public static function fromRequest($request, ?Patient $patient = null): Patient
    {
        $patient = $patient ?? new Patient();
        $patient->first_name = isset($request['first_name']) ? $request['first_name'] : $patient->first_name;
        $patient->last_name = isset($request['last_name']) ? $request['last_name'] : $patient->last_name;
        $patient->cuit = isset($request['cuit']) ? $request['cuit'] : $patient->cuit;
        $patient->email = isset($request['email']) ? $request['email'] : $patient->email;
        $patient->phone = isset($request['phone']) ? $request['phone'] : $patient->phone;
        $patient->birth_date = isset($request['birth_date']) ? $request['birth_date'] : $patient->birth_date;
        $patient->address = isset($request['address']) ? $request['address'] : $patient->address;
        $patient->insurance_provider = isset($request['insurance_provider']) ? $request['insurance_provider'] : $patient->insurance_provider;

        return $patient;
    }
}
