<?php

namespace App\Http\Controllers;

use App\Factories\StockMovementFactory;
use App\Http\Requests\StockMovement\IndexStockMovementsRequest;
use App\Http\Requests\StockMovement\StoreStockMovementRequest;
use App\Models\Product;
use App\Models\StockMovement;

class StockMovementController extends Controller
{
    public function index(IndexStockMovementsRequest $request)
    {
        $validated = $request->validated();
        $cantidad = $validated['cantidad'] ?? 10;
        $pagina = $validated['pagina'] ?? 1;

        $query = StockMovement::query()->with(['product', 'user']);

        if (isset($validated['product_id'])) {
            $query->where('product_id', $validated['product_id']);
        }

        if (isset($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        $paginador = $query->orderBy('created_at', 'desc')->paginate($cantidad, ['*'], 'page', $pagina);

        return response()->json([
            'data' => $paginador->items(),
            'current_page' => $paginador->currentPage(),
            'total_pages' => $paginador->lastPage(),
            'total_registros' => $paginador->total(),
        ]);
    }

    public function store(StoreStockMovementRequest $request)
    {
        $validated = $request->validated();

        // Set the authenticated user as the one performing the movement
        $validated['user_id'] = auth()->id();

        $movement = StockMovementFactory::fromRequest($validated);
        $movement->save();

        // Update the product stock
        $product = Product::find($validated['product_id']);
        if ($product) {
            if ($validated['type'] === 'in') {
                $product->stock += $validated['quantity'];
            } elseif ($validated['type'] === 'out') {
                $product->stock -= $validated['quantity'];
            } else {
                // adjustment: set absolute value
                $product->stock = $validated['quantity'];
            }
            $product->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Stock movement created successfully',
            'stock_movement' => $movement->load(['product', 'user']),
        ], 201);
    }

    public function show($id)
    {
        $movement = StockMovement::with(['product', 'user'])->find($id);

        if (! $movement) {
            return response()->json([
                'success' => false,
                'message' => 'Stock movement not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'stock_movement' => $movement,
        ]);
    }
}
