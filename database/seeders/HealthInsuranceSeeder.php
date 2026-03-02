<?php

namespace Database\Seeders;

use App\Models\HealthInsurance;
use Illuminate\Database\Seeder;

class HealthInsuranceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $insurances = [
            'OSDE 210',
            'OSDE 310',
            'OSDE 410',
            'Galeno 220',
            'Galeno 330',
            'Swiss Medical',
            'Medifé',
            'Omint',
            'Particular',
        ];

        foreach ($insurances as $name) {
            HealthInsurance::firstOrCreate(['name' => $name]);
        }
    }
}
