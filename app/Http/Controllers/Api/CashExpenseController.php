<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CashExpense\StoreCashExpenseRequest;
use App\Models\CashExpense;
use App\Models\CashShift;

class CashExpenseController extends Controller
{
    /**
     * Register a new cash expense for the given open cash shift.
     */
    public function store(StoreCashExpenseRequest $request)
    {
        $validated = $request->validated();

        /** @var CashShift $shift */
        $shift = CashShift::findOrFail($validated['cash_shift_id']);

        if ($shift->status !== 'open') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede registrar un egreso: el turno de caja no está abierto.',
            ], 422);
        }

        $expense = CashExpense::create([
            'cash_shift_id' => $validated['cash_shift_id'],
            'user_id'       => auth()->id(),
            'amount'        => $validated['amount'],
            'description'   => $validated['description'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Egreso registrado exitosamente.',
            'data'    => $expense,
        ], 201);
    }
}
