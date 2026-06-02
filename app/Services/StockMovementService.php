<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockMovementService
{
    /**
     * Record an outgoing stock movement for a product:
     * 1. Locks the product row.
     * 2. Validates that there is enough stock (unless $allowNegative).
     * 3. Decrements Product.stock.
     * 4. Inserts the StockMovement ledger row with previous_stock.
     *
     * Must be called inside a DB::transaction by the caller.
     */
    public function recordOut(int $productId, int $quantity, string $reason, ?int $userId = null, ?string $notes = null): StockMovement
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => ['La cantidad debe ser mayor a 0.'],
            ]);
        }

        $product = Product::where('id', $productId)->lockForUpdate()->first();

        if (! $product) {
            throw ValidationException::withMessages([
                'product_id' => ["El producto con ID {$productId} no existe."],
            ]);
        }

        if ($product->stock < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => [
                    "Stock insuficiente para '{$product->name}'. Disponible: {$product->stock}, solicitado: {$quantity}.",
                ],
            ]);
        }

        $previousStock = $product->stock;
        $product->stock -= $quantity;
        $product->save();

        return StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $userId ?? auth()->id(),
            'type' => 'out',
            'quantity' => $quantity,
            'previous_stock' => $previousStock,
            'reason' => $reason,
            'notes' => $notes,
        ]);
    }

    /**
     * Record an incoming stock movement (compensatory entry, return, etc.).
     * Must be called inside a DB::transaction by the caller.
     */
    public function recordIn(int $productId, int $quantity, string $reason, ?int $userId = null, ?string $notes = null): StockMovement
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => ['La cantidad debe ser mayor a 0.'],
            ]);
        }

        $product = Product::where('id', $productId)->lockForUpdate()->first();

        if (! $product) {
            throw ValidationException::withMessages([
                'product_id' => ["El producto con ID {$productId} no existe."],
            ]);
        }

        $previousStock = $product->stock;
        $product->stock += $quantity;
        $product->save();

        return StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $userId ?? auth()->id(),
            'type' => 'in',
            'quantity' => $quantity,
            'previous_stock' => $previousStock,
            'reason' => $reason,
            'notes' => $notes,
        ]);
    }
}
