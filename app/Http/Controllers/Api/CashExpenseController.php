<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CashExpense\StoreCashExpenseRequest;
use App\Http\Resources\CashExpenseResource;
use App\Models\CashExpense;
use App\Models\CashShift;

class CashExpenseController extends Controller
{
    /**
     * List all cash expenses, optionally filtered by date.
     * GET /cash-expenses?date=2026-03-04
     */
    public function index()
    {
        $query = CashExpense::with(['user', 'cashShift'])
            ->orderBy('created_at', 'desc');

        if (request()->has('date') && request('date')) {
            $query->whereDate('created_at', request('date'));
        }

        $expenses = $query->get();

        return CashExpenseResource::collection($expenses);
    }

    /**
     * Register a new cash expense for the given open cash shift.
     */
    public function store(StoreCashExpenseRequest $request)
    {
        $validated = $request->validated();

        return \Illuminate\Support\Facades\DB::transaction(function () use ($validated) {
            // Resolve the open shift server-side and lock it for the duration
            // of this transaction so concurrent open/close operations don't
            // race against the expense write.
            $shift = CashShift::where('status', 'open')->lockForUpdate()->first();

            if (! $shift) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede registrar un egreso: no hay un turno de caja abierto.',
                ], 422);
            }

            $expense = CashExpense::create([
                'cash_shift_id' => $shift->id,
                'user_id'       => auth()->id(),
                'amount'        => $validated['amount'],
                'description'   => $validated['description'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Egreso registrado exitosamente.',
                'data'    => new CashExpenseResource($expense),
            ], 201);
        });
    }
}
