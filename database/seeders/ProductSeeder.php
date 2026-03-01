<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Cache lookups
        $brand = fn (string $name) => Brand::where('name', $name)->first()?->id;
        $category = fn (string $name) => Category::where('name', $name)->first()?->id;
        $subcategory = fn (string $name) => Subcategory::where('name', $name)->first()?->id;

        $products = [
            [
                'name' => 'Protector Solar SPF 50',
                'description' => 'Crema solar de amplio espectro UVA/UVB',
                'price' => 3500,
                'stock' => 50,
                'min_stock' => 10,
                'brand' => 'La Roche-Posay',
                'category' => 'Skin Care',
                'subcategory' => 'Protección Solar',
                'is_for_sale' => true,
                'is_supply' => false,
            ],
            [
                'name' => 'Vitamina C Sérum 20%',
                'description' => 'Suero antioxidante iluminador',
                'price' => 5200,
                'stock' => 30,
                'min_stock' => 5,
                'brand' => 'SkinCeuticals',
                'category' => 'Skin Care',
                'subcategory' => 'Serums',
                'is_for_sale' => true,
                'is_supply' => false,
            ],
            [
                'name' => 'Retinol Crema Noche 0.5%',
                'description' => 'Crema regeneradora nocturna con retinol',
                'price' => 4800,
                'stock' => 20,
                'min_stock' => 5,
                'brand' => 'CeraVe',
                'category' => 'Skin Care',
                'subcategory' => 'Serums',
                'is_for_sale' => true,
                'is_supply' => false,
            ],
            [
                'name' => 'Ácido Hialurónico Gel',
                'description' => 'Gel hidratante concentrado',
                'price' => 2900,
                'stock' => 40,
                'min_stock' => 8,
                'brand' => 'Vichy',
                'category' => 'Skin Care',
                'subcategory' => 'Hidratantes',
                'is_for_sale' => true,
                'is_supply' => true,
            ],
            [
                'name' => 'Gel de Limpieza Purificante',
                'description' => 'Gel facial para pieles grasas y mixtas',
                'price' => 1800,
                'stock' => 35,
                'min_stock' => 10,
                'brand' => 'Bioderma',
                'category' => 'Skin Care',
                'subcategory' => 'Limpieza',
                'is_for_sale' => true,
                'is_supply' => false,
            ],
            [
                'name' => 'Crema Hidratante Reparadora',
                'description' => 'Hidratante para pieles sensibles y secas',
                'price' => 3200,
                'stock' => 25,
                'min_stock' => 5,
                'brand' => 'CeraVe',
                'category' => 'Skin Care',
                'subcategory' => 'Hidratantes',
                'is_for_sale' => true,
                'is_supply' => true,
            ],
            [
                'name' => 'Agua Termal Spray 150ml',
                'description' => 'Agua termal calmante en formato spray',
                'price' => 1500,
                'stock' => 60,
                'min_stock' => 15,
                'brand' => 'Eucerin',
                'category' => 'Skin Care',
                'subcategory' => 'Hidratantes',
                'is_for_sale' => true,
                'is_supply' => true,
            ],
            [
                'name' => 'Base Fluida Couvrance',
                'description' => 'Base maquillaje correctora alta cobertura',
                'price' => 4500,
                'stock' => 15,
                'min_stock' => 3,
                'brand' => 'La Roche-Posay',
                'category' => 'Maquillaje',
                'subcategory' => 'Base',
                'is_for_sale' => true,
                'is_supply' => false,
            ],
            [
                'name' => 'Rodillo Facial de Jade',
                'description' => 'Rodillo masajeador de piedra jade natural',
                'price' => 2200,
                'stock' => 12,
                'min_stock' => 3,
                'brand' => null,
                'category' => 'Accesorios',
                'subcategory' => 'Rodillos',
                'is_for_sale' => true,
                'is_supply' => false,
            ],
            [
                'name' => 'Funda de Satén para Almohada',
                'description' => 'Funda de satén anti-fricción para el cabello y la piel',
                'price' => 1800,
                'stock' => 20,
                'min_stock' => 5,
                'brand' => null,
                'category' => 'Accesorios',
                'subcategory' => 'Fundas de Satén',
                'is_for_sale' => true,
                'is_supply' => false,
            ],
            [
                'name' => 'Jeringas Descartables 5ml (x100)',
                'description' => 'Caja de 100 jeringas descartables de 5ml',
                'price' => 800,
                'stock' => 10,
                'min_stock' => 5,
                'brand' => null,
                'category' => 'Insumos Médicos',
                'subcategory' => 'Jeringas',
                'is_for_sale' => false,
                'is_supply' => true,
            ],
            [
                'name' => 'Gasas Estériles 10x10 (x100)',
                'description' => 'Paquete de 100 gasas estériles',
                'price' => 350,
                'stock' => 30,
                'min_stock' => 10,
                'brand' => null,
                'category' => 'Insumos Médicos',
                'subcategory' => 'Gasas',
                'is_for_sale' => false,
                'is_supply' => true,
            ],
            [
                'name' => 'Guantes de Nitrilo Talle M (x100)',
                'description' => 'Caja de 100 guantes de nitrilo sin polvo',
                'price' => 600,
                'stock' => 3,
                'min_stock' => 5,
                'brand' => null,
                'category' => 'Insumos Médicos',
                'subcategory' => 'Guantes',
                'is_for_sale' => false,
                'is_supply' => true,
            ],
            [
                'name' => 'Effaclar Duo+ Corrector',
                'description' => 'Corrector anti-imperfecciones con niacinamida',
                'price' => 3800,
                'stock' => 18,
                'min_stock' => 4,
                'brand' => 'La Roche-Posay',
                'category' => 'Maquillaje',
                'subcategory' => 'Corrector',
                'is_for_sale' => true,
                'is_supply' => false,
            ],
            [
                'name' => 'Esponja Konjac Facial',
                'description' => 'Esponja natural de konjac para limpieza facial suave',
                'price' => 950,
                'stock' => 8,
                'min_stock' => 3,
                'brand' => null,
                'category' => 'Accesorios',
                'subcategory' => 'Esponjas',
                'is_for_sale' => true,
                'is_supply' => false,
            ],
        ];

        foreach ($products as $data) {
            Product::firstOrCreate(
                ['name' => $data['name']],
                [
                    'description' => $data['description'],
                    'price' => $data['price'],
                    'stock' => $data['stock'],
                    'min_stock' => $data['min_stock'],
                    'brand_id' => $data['brand'] ? $brand($data['brand']) : null,
                    'category_id' => $data['category'] ? $category($data['category']) : null,
                    'subcategory_id' => $data['subcategory'] ? $subcategory($data['subcategory']) : null,
                    'is_for_sale' => $data['is_for_sale'],
                    'is_supply' => $data['is_supply'],
                ],
            );
        }
    }
}
