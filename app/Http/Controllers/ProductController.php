<?php

namespace App\Http\Controllers;

use App\Actions\Product\ImportProductsAction;
use App\Factories\ProductFactory;
use App\Http\Requests\Product\ImportProductRequest;
use App\Http\Requests\Product\IndexProductsRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;

class ProductController extends Controller
{
    public function index(IndexProductsRequest $request)
    {
        $validated = $request->validated();
        $cantidad = $validated['cantidad'] ?? 10;
        $pagina = $validated['pagina'] ?? 1;

        $query = Product::with(['brand:id,name']);

        // ── Trashed filter (archived products) ─────────────────────────
        if (isset($validated['trashed']) && $validated['trashed'] === 'true') {
            $query->onlyTrashed();
        }

        // ── Low stock server-side filter ─────────────────────────────────
        if (isset($validated['stock_status']) && $validated['stock_status'] === 'low') {
            $query->whereColumn('stock', '<=', 'min_stock');
        }

        // ── Text search (combined with other filters) ───────────────────
        if (isset($validated['name'])) {
            $query->where('name', 'ilike', '%'.$validated['name'].'%');
        }

        // ── Filter by category ──────────────────────────────────────────
        if (isset($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }

        // ── Filter by brand ─────────────────────────────────────────────
        if (isset($validated['brand_id'])) {
            $query->where('brand_id', $validated['brand_id']);
        }

        // ── Filter by product type ──────────────────────────────────────
        if (isset($validated['is_for_sale'])) {
            $query->where('is_for_sale', filter_var($validated['is_for_sale'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($validated['is_supply'])) {
            $query->where('is_supply', filter_var($validated['is_supply'], FILTER_VALIDATE_BOOLEAN));
        }

        // ── Sorting ─────────────────────────────────────────────────────
        $sort = $validated['sort'] ?? null;
        match ($sort) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'name_asc' => $query->orderBy('name', 'asc'),
            'name_desc' => $query->orderBy('name', 'desc'),
            'stock_asc' => $query->orderBy('stock', 'asc'),
            'stock_desc' => $query->orderBy('stock', 'desc'),
            default => $query->latest(),
        };

        $paginador = $query->paginate($cantidad, ['*'], 'page', $pagina);

        return ProductResource::collection($paginador);
    }

    public function kpis(IndexProductsRequest $request)
    {
        $validated = $request->validated();

        $query = Product::query();

        // ── Same trashed/stock filters as index ──────────────────────────
        if (isset($validated['trashed']) && $validated['trashed'] === 'true') {
            $query->onlyTrashed();
        }
        if (isset($validated['stock_status']) && $validated['stock_status'] === 'low') {
            $query->whereColumn('stock', '<=', 'min_stock');
        }

        // ── Same filters as index ─────────────────────────────────────────
        if (isset($validated['name'])) {
            $query->where('name', 'ilike', '%'.$validated['name'].'%');
        }
        if (isset($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }
        if (isset($validated['brand_id'])) {
            $query->where('brand_id', $validated['brand_id']);
        }
        if (isset($validated['is_for_sale'])) {
            $query->where('is_for_sale', filter_var($validated['is_for_sale'], FILTER_VALIDATE_BOOLEAN));
        }
        if (isset($validated['is_supply'])) {
            $query->where('is_supply', filter_var($validated['is_supply'], FILTER_VALIDATE_BOOLEAN));
        }

        return response()->json([
            'total_products' => (clone $query)->count(),
            'total_value' => (float) (clone $query)->selectRaw('COALESCE(SUM(price * stock), 0) as total')->value('total'),
            'low_stock_count' => (clone $query)->whereColumn('stock', '<=', 'min_stock')->count(),
            'active_products' => (clone $query)->where('stock', '>', 0)->count(),
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();

        $product = ProductFactory::fromRequest($validated);
        $product->save();

        return (new ProductResource($product->load(['brand:id,name'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Product $product)
    {
        return new ProductResource($product->load(['brand:id,name']));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $validated = $request->validated();

        $product = ProductFactory::fromRequest($validated, $product);
        $product->save();

        return new ProductResource($product->load(['brand:id,name']));
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'message' => 'Product archived successfully',
        ]);
    }

    public function restore(int $id)
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();

        return new ProductResource($product->load(['brand:id,name']));
    }

    public function adjustmentReasons()
    {
        return response()->json([
            'data' => [
                ['value' => 'sale', 'label' => 'Venta'],
                ['value' => 'expiry', 'label' => 'Caducidad'],
                ['value' => 'breakage', 'label' => 'Rotura'],
                ['value' => 'internal_use_adj', 'label' => 'Uso Interno'],
            ],
        ]);
    }

    public function import(ImportProductRequest $request, ImportProductsAction $action)
    {
        $result = $action->execute($request->file('file'));

        return response()->json([
            'message' => $result['message'],
            'imported_count' => $result['imported_count'],
            'errors' => $result['errors'],
        ], $result['status'] === 200 ? 200 : 422);
    }
}
