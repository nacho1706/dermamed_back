<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Service;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            [
                'name' => 'Consulta Dermatológica General',
                'description' => 'Evaluación integral de la piel, diagnóstico y tratamiento.',
                'price' => 25000.00,
                'duration_minutes' => 30,
            ],
            [
                'name' => 'Limpieza de Cutis Profunda',
                'description' => 'Tratamiento facial para eliminar impurezas, puntos negros y células muertas.',
                'price' => 18000.00,
                'duration_minutes' => 60,
            ],
            [
                'name' => 'Aplicación de Toxina Botulínica (Botox)',
                'description' => 'Tratamiento para reducir arrugas de expresión en frente, entrecejo y patas de gallo.',
                'price' => 150000.00,
                'duration_minutes' => 45,
            ],
            [
                'name' => 'Peeling Químico',
                'description' => 'Exfoliación química para renovar la piel y mejorar manchas y cicatrices.',
                'price' => 35000.00,
                'duration_minutes' => 45,
            ],
            [
                'name' => 'Depilación Láser (Zona Chica)',
                'description' => 'Sesión de depilación láser en zonas pequeñas (bozo, mentón, axilas).',
                'price' => 12000.00,
                'duration_minutes' => 15,
            ],
        ];

        foreach ($services as $serviceData) {
            Service::firstOrCreate(
                ['name' => $serviceData['name']],
                $serviceData
            );
        }
    }
}
