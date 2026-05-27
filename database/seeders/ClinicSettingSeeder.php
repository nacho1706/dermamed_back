<?php

namespace Database\Seeders;

use App\Services\ClinicSettingService;
use Illuminate\Database\Seeder;

class ClinicSettingSeeder extends Seeder
{
    public function run(): void
    {
        $svc = app(ClinicSettingService::class);
        foreach (ClinicSettingService::DEFAULTS as $key => $value) {
            $svc->set($key, $value);
        }
    }
}
