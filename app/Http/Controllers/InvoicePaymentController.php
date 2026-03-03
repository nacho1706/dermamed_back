<?php

namespace App\Http\Controllers;

use App\Factories\InvoicePaymentFactory;
use App\Http\Requests\InvoicePayment\StoreInvoicePaymentRequest;
use App\Http\Resources\InvoicePaymentResource;
use App\Models\Invoice;
use App\Models\InvoicePayment;

class InvoicePaymentController extends Controller
{
    public function store(StoreInvoicePaymentRequest $request, Invoice $invoice)
    {
        $validated = $request->validated();

        $cashShift = \App\Models\CashShift::where('status', 'open')->first();
        if (!$cashShift) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'cash_shift' => ['No hay una caja abierta. Cerrala o abrí una nueva antes de registrar pagos.'],
            ]);
        }

        $totalPaid = (float) $invoice->payments()->sum('amount');
        $remainingBalance = (float) bcsub($invoice->total_amount, $totalPaid, 2);

        if ((float) $validated['amount'] > $remainingBalance) {
            abort(422, 'El monto supera el saldo pendiente de la factura.');
        }

        $payment = InvoicePayment::create([
            'invoice_id' => $invoice->id,
            'payment_method_id' => $validated['payment_method_id'],
            'amount' => $validated['amount'],
            'payment_date' => $validated['payment_date'] ?? now(),
            'cash_shift_id' => $cashShift->id,
        ]);

        $payment->load('paymentMethod');

        // Recalculate Invoice Status
        $totalPaid = $invoice->payments()->sum('amount');
        $isPaid = bccomp($totalPaid, $invoice->total_amount, 2) >= 0;

        $invoice->update([
            'status' => $isPaid ? 'paid' : 'pending',
        ]);

        \App\Models\InvoiceHistory::create([
            'invoice_id' => $invoice->id,
            'user_id' => auth()->id(),
            'action' => 'payment_added',
            'description' => 'Pago registrado por $' . number_format($validated['amount'], 2, ',', '.') . '. Estado actual: ' . ($isPaid ? 'Pagada' : 'Pendiente'),
        ]);

        return (new InvoicePaymentResource($payment))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Invoice $invoice, InvoicePayment $payment)
    {
        $payment->load('paymentMethod');

        return new InvoicePaymentResource($payment);
    }

    public function destroy(Invoice $invoice, InvoicePayment $payment)
    {
        $payment->delete();

        return response()->json([
            'message' => 'Invoice payment deleted successfully',
        ]);
    }
}
