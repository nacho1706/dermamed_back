<?php

namespace Database\Seeders;

use App\Models\HealthInsurance;
use App\Models\Patient;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Paciente con todos los datos completos
        Patient::firstOrCreate(
            ['dni' => '30123456'],
            [
                'first_name' => 'Juan',
                'last_name' => 'Pérez',
                'cuit' => '20301234568',
                'email' => 'juan.perez@example.com',
                'phone' => '+541144445555',
                'birth_date' => '1985-06-15',
                'street' => 'Av. Libertador',
                'street_number' => '1500',
                'floor' => '5',
                'apartment' => 'A',
                'city' => 'CABA',
                'province' => 'Buenos Aires',
                'zip_code' => 'C1425',
                'country' => 'Argentina',
                'health_insurance_id' => HealthInsurance::where('name', 'OSDE 310')->first()?->id,
                'affiliate_number' => '123456789',
            ]
        );

        // 2. Paciente con solo lo indispensable (first_name, last_name, dni)
        Patient::firstOrCreate(
            ['dni' => '40987654'],
            [
                'first_name' => 'María',
                'last_name' => 'Gómez',
            ]
        );

        // 3. Paciente sin email pero con teléfono
        Patient::firstOrCreate(
            ['dni' => '35111222'],
            [
                'first_name' => 'Carlos',
                'last_name' => 'Rodríguez',
                'phone' => '1155556666',
                'birth_date' => '1990-03-20',
            ]
        );

        // 4. Paciente sin teléfono pero con email
        Patient::firstOrCreate(
            ['dni' => '28333444'],
            [
                'first_name' => 'Ana',
                'last_name' => 'Martínez',
                'email' => 'ana.martinez@ejemplo.com',
                'birth_date' => '1980-11-10',
            ]
        );

        // 5. Paciente con combinación rara (nombres o calles extremadamente largos o extranjeros)
        Patient::firstOrCreate(
            ['dni' => '99999999'], // probando DNI correcto
            [
                'first_name' => 'Pablo Diego José Francisco de Paula',
                'last_name' => 'Juan Nepomuceno María de los Remedios Cipriano de la Santísima Trinidad Ruiz y Picasso',
                'email' => 'nombre.muy.largo.ruiz.picasso@ejemplo.con.dominio.largo.net',
                'city' => 'Un lugar muy lejano y recóndito de una provincia olvidada',
            ]
        );

        // 6. Paciente con todo menos la dirección
        Patient::firstOrCreate(
            ['dni' => '22555666'],
            [
                'first_name' => 'Laura',
                'last_name' => 'Sánchez',
                'cuit' => '27225556669',
                'email' => 'laura.sanchez@ejemplo.com',
                'phone' => '1143214321',
                'birth_date' => '1975-08-05',
                'health_insurance_id' => HealthInsurance::where('name', 'Galeno 220')->first()?->id,
                'affiliate_number' => '987654321',
            ]
        );

        // 7. Paciente con piso y departamento pero sin número ni calle (error humano común simulado)
        Patient::firstOrCreate(
            ['dni' => '39888999'],
            [
                'first_name' => 'Diego',
                'last_name' => 'López',
                'floor' => 'PB',
                'apartment' => 'C',
                // No street or street_number provided
                'city' => 'Córdoba',
                'province' => 'Córdoba',
            ]
        );
    }
}
