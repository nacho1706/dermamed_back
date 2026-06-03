<?php

namespace App\Services;

use App\Models\CashShift;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashShiftService
{
    /**
     * Get the currently open cash shift, or null.
     */
    public function getCurrentShift(): ?CashShift
    {
        return CashShift::where('status', 'open')
            ->with(['openedBy', 'closedBy', 'payments.paymentMethod', 'payments.invoice.patient', 'expenses'])
            ->first();
    }

    /**
     * Open a new cash shift.
     *
     * Wrapped in a transaction with a LOCK on the open-shift slot so two
     * concurrent calls don't both pass the "is there an open shift?" check.
     * A partial unique index in Postgres provides the last line of defense:
     * even with a race the DB would reject the second INSERT.
     *
     * @throws ValidationException
     */
    public function openShift(array $data): CashShift
    {
        return DB::transaction(function () use ($data) {
            $existing = CashShift::where('status', 'open')->lockForUpdate()->exists();

            if ($existing) {
                throw ValidationException::withMessages([
                    'cash_shift' => ['Ya existe una caja abierta. Cerrala antes de abrir una nueva.'],
                ]);
            }

            $shift = CashShift::create([
                'opening_time' => now(),
                'initial_balance' => $data['opening_balance'],
                'user_id_opened' => auth()->id(),
                'status' => 'open',
            ]);

            $shift->load(['openedBy']);

            return $shift;
        });
    }

    /**
     * Close the currently open cash shift.
     *
     * @throws ValidationException
     */
    public function closeShift(array $data): CashShift
    {
        return DB::transaction(function () use ($data) {
            $shift = CashShift::where('status', 'open')->lockForUpdate()->first();

            if (! $shift) {
                throw ValidationException::withMessages([
                    'cash_shift' => ['No hay una caja abierta para cerrar.'],
                ]);
            }

            $shift->load('payments');

            // Pending invoices linked to THIS shift via any of their payments.
            // The previous fallback by date was ambiguous when multiple shifts
            // happen in the same day (morning/afternoon).
            $pendingCount = \App\Models\Invoice::where('status', 'pending')
                ->whereHas('payments', fn ($p) => $p->where('cash_shift_id', $shift->id))
                ->count();

            if ($pendingCount > 0) {
                throw ValidationException::withMessages([
                    'cash_shift' => ['No se puede cerrar la caja: Existen facturas pendientes de cobro en este turno.'],
                ]);
            }

            $finalBalance = (float) ($data['closing_balance'] ?? $data['final_balance'] ?? 0);

            // Persist the snapshot of system_balance and the conciliation
            // difference at close time so historical reports don't drift if
            // payments/expenses change shape later.
            // Use a portable case-insensitive comparison so the test suite
            // (SQLite, no ILIKE) and production (Postgres) both work.
            $totalCashIn = (float) $shift->payments()
                ->whereHas('paymentMethod', fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', ['%efectivo%']))
                ->sum('amount');
            $totalExpenses = (float) $shift->expenses()->sum('amount');
            $systemBalance = (float) $shift->initial_balance + $totalCashIn - $totalExpenses;
            $difference = $finalBalance - $systemBalance;

            $shift->update([
                'closing_time' => now(),
                'final_balance' => $finalBalance,
                'system_balance' => $systemBalance,
                'difference' => $difference,
                'justification' => $data['justification'] ?? null,
                'user_id_closed' => auth()->id(),
                'status' => 'closed',
            ]);

            $shift->load(['openedBy', 'closedBy', 'payments.paymentMethod']);

            return $shift;
        });
    }
}
