<?php

namespace App\Factories;

use App\Models\Product;

class ProductFactory
{
    public static function fromRequest($request, ?Product $product = null): Product
    {
        $product = $product ?? new Product;
        $product->name = isset($request['name']) ? $request['name'] : $product->name;
        $product->description = isset($request['description']) ? $request['description'] : $product->description;
        $product->price = isset($request['price']) ? $request['price'] : $product->price;
        $product->min_stock = isset($request['min_stock']) ? $request['min_stock'] : $product->min_stock;

        // ── Dependencies ────────────────────────────────────────────────
        if (array_key_exists('brand_id', $request)) {
            $product->brand_id = $request['brand_id'];
        }

        return $product;
    }
}
