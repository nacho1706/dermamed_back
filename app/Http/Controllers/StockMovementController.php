<?php

namespace App\Http\Controllers;

use App\Factories\StockMovementFactory;
use App\Http\Requests\StockMovement\IndexStockMovementsRequest;
use App\Http\Requests\StockMovement\StoreStockMovementRequest;
use App\Http\Resources\StockMovementResource;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

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

        $paginador = $query->orderBy('created_at', 'desc')
            ->when(isset($validated['date_from']), fn ($q) => $q->where('created_at', '>=', $validated['date_from']))
            ->when(isset($validated['date_to']), fn ($q) => $q->where('created_at', '<=', $validated['date_to'].' 23:59:59'))
            ->paginate($cantidad, ['*'], 'page', $pagina);

        return StockMovementResource::collection($paginador);
    }

    public function store(StoreStockMovementRequest $request, Product $product)
    {
        $validated = $request->validated();
        $validated['user_id'] = auth()->id();
        $validated['product_id'] = $product->id;

        $movement = DB::transaction(function () use ($validated, $product) {
            $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->first();

            if (! $lockedProduct) {
                abort(404, 'Producto no encontrado');
            }

            // ── Ledger Pattern: capture current stock before any mutation ──
            $previousStock = $lockedProduct->stock;

            // ── Calculate new stock based on movement type ─────────────────
            if ($validated['type'] === 'in') {
                // Entry: add to current stock
                $newStock = $previousStock + $validated['quantity'];
            } elseif ($validated['type'] === 'out') {
                // Exit: subtract from current stock
                $newStock = $previousStock - $validated['quantity'];

                if ($newStock < 0) {
                    abort(response()->json([
                        'message' => 'La cantidad de salida supera el stock disponible.',
                        'errors' => [
                            'quantity' => ["No se pueden retirar {$validated['quantity']} unidades. Stock disponible: {$previousStock}."],
                        ],
                    ], 422));
                }
            } else {
                // adjustment: absolute overwrite — quantity IS the new stock level
                $newStock = $validated['quantity'];
            }

            // ── Persist the movement with the ledger snapshot ───────────────
            $validated['previous_stock'] = $previousStock;
            $movement = StockMovementFactory::fromRequest($validated);
            $movement->save();

            // ── Update product stock ────────────────────────────────────────
            $lockedProduct->stock = $newStock;
            $lockedProduct->save();

            return $movement;
        });

        $movement->load(['product', 'user']);

        return (new StockMovementResource($movement))
            ->response()
            ->setStatusCode(201);
    }

    public function show(StockMovement $stockMovement)
    {
        $stockMovement->load(['product', 'user']);

        return new StockMovementResource($stockMovement);
    }
}
