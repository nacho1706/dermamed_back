<?php

namespace App\Factories;

use App\Models\StockMovement;

class StockMovementFactory
{
    public static function fromRequest($request, ?StockMovement $movement = null): StockMovement
    {
        $movement = $movement ?? new StockMovement;
        $movement->product_id = isset($request['product_id']) ? $request['product_id'] : $movement->product_id;
        $movement->user_id = isset($request['user_id']) ? $request['user_id'] : $movement->user_id;
        $movement->type = isset($request['type']) ? $request['type'] : $movement->type;
        $movement->quantity = isset($request['quantity']) ? $request['quantity'] : $movement->quantity;
        $movement->reason = isset($request['reason']) ? $request['reason'] : $movement->reason;
        $movement->notes = isset($request['notes']) ? $request['notes'] : $movement->notes;

        return $movement;
    }
}
