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
            'opening_balance' => $data['opening_balance'],
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

        $shift->update([
            'closing_time' => now(),
            'closing_balance' => $data['closing_balance'],
            'justification' => $data['justification'] ?? null,
            'user_id_closed' => auth()->id(),
            'status' => 'closed',
        ]);

        $shift->load(['openedBy', 'closedBy', 'payments.paymentMethod']);

        return $shift;
    }
}
