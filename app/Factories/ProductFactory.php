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
        $product->stock = isset($request['stock']) ? $request['stock'] : $product->stock;
        $product->min_stock = isset($request['min_stock']) ? $request['min_stock'] : $product->min_stock;

        // ── New fields ──────────────────────────────────────────────────
        if (array_key_exists('brand_id', $request)) {
            $product->brand_id = $request['brand_id'];
        }
        if (array_key_exists('category_id', $request)) {
            $product->category_id = $request['category_id'];
        }
        if (array_key_exists('subcategory_id', $request)) {
            $product->subcategory_id = $request['subcategory_id'];
        }
        if (array_key_exists('is_for_sale', $request)) {
            $product->is_for_sale = $request['is_for_sale'];
        }
        if (array_key_exists('is_supply', $request)) {
            $product->is_supply = $request['is_supply'];
        }

        return $product;
    }
}
