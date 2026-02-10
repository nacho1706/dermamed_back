<?php

namespace Database\Seeders;

use App\Models\VoucherType;
use Illuminate\Database\Seeder;

class VoucherTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = ['Factura A', 'Factura B', 'Recibo X'];

        foreach ($types as $type) {
            VoucherType::firstOrCreate(['name' => $type]);
        }
    }
}
