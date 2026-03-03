<?php

namespace App\Services;

use App\Models\CashShift;
use Illuminate\Validation\ValidationException;

class CashShiftService
{
    /**
     * Get the currently open cash shift, or null.
     */
    public function getCurrentShift(): ?CashShift
    {
        return CashShift::where('status', 'open')
            ->with(['openedBy', 'closedBy', 'payments.paymentMethod', 'payments.invoice.patient'])
            ->first();
    }

    /**
     * Open a new cash shift.
     *
     * @throws ValidationException
     */
    public function openShift(array $data): CashShift
    {
        $existing = CashShift::where('status', 'open')->exists();

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
    }

    /**
     * Close the currently open cash shift.
     *
     * @throws ValidationException
     */
    public function closeShift(array $data): CashShift
    {
        $shift = CashShift::where('status', 'open')->first();

        if (! $shift) {
            throw ValidationException::withMessages([
                'cash_shift' => ['No hay una caja abierta para cerrar.'],
            ]);
        }

        $shift->load('payments');

        // ── Business Rule: no pending invoices in this shift ──────────────
        $pendingCount = \App\Models\Invoice::where('status', 'pending')
            ->where(function ($q) use ($shift) {
                // Primary: invoices whose payments belong to this cash shift
                $q->whereHas('payments', fn($p) => $p->where('cash_shift_id', $shift->id))
                  // Fallback: invoices created today (covers invoices with no payments yet)
                  ->orWhereDate('date', $shift->opening_time->toDateString());
            })
            ->count();

        if ($pendingCount > 0) {
            throw ValidationException::withMessages([
                'cash_shift' => ['No se puede cerrar la caja: Existen facturas pendientes de cobro en este turno.'],
            ]);
        }
        // ─────────────────────────────────────────────────────────────────

        $shift->update([
            'closing_time' => now(),
            'final_balance' => $data['closing_balance'] ?? $data['final_balance'] ?? 0,
            'justification' => $data['justification'] ?? null,
            'user_id_closed' => auth()->id(),
            'status' => 'closed',
        ]);

        $shift->load(['openedBy', 'closedBy', 'payments.paymentMethod']);

        return $shift;
    }
}
