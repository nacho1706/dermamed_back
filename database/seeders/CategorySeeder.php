<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $structure = [
            'Skin Care' => ['Hidratantes', 'Serums', 'Limpieza', 'Protección Solar'],
            'Maquillaje' => ['Base', 'Corrector', 'Labiales'],
            'Accesorios' => ['Fundas de Satén', 'Rodillos', 'Esponjas'],
            'Insumos Médicos' => ['Jeringas', 'Gasas', 'Guantes', 'Agujas'],
        ];

        foreach ($structure as $categoryName => $subcategories) {
            $category = Category::firstOrCreate(['name' => $categoryName]);

            foreach ($subcategories as $subName) {
                Subcategory::firstOrCreate([
                    'name' => $subName,
                    'category_id' => $category->id,
                ]);
            }
        }
    }
}
