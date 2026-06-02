<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoicePayment\StoreInvoicePaymentRequest;
use App\Http\Resources\InvoicePaymentResource;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use Illuminate\Support\Facades\DB;

class InvoicePaymentController extends Controller
{
    public function store(StoreInvoicePaymentRequest $request, Invoice $invoice)
    {
        $validated = $request->validated();

        return DB::transaction(function () use ($validated, $invoice) {
            $cashShift = \App\Models\CashShift::where('status', 'open')->lockForUpdate()->first();
            if (! $cashShift) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'cash_shift' => ['No hay una caja abierta. Cerrala o abrí una nueva antes de registrar pagos.'],
                ]);
            }

            // Lock the invoice so two concurrent payments compute the same
            // totalPaid view and don't race the status update.
            $invoice = Invoice::lockForUpdate()->findOrFail($invoice->id);

            $payment = InvoicePayment::create([
                'invoice_id' => $invoice->id,
                'payment_method_id' => $validated['payment_method_id'],
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'] ?? now(),
                'cash_shift_id' => $cashShift->id,
            ]);

            $payment->load('paymentMethod');

            $totalPaid = $invoice->payments()->sum('amount');
            $isPaid = bccomp($totalPaid, $invoice->total_amount, 2) >= 0;

            $invoice->update([
                'status' => $isPaid ? 'paid' : 'pending',
            ]);

            \App\Models\InvoiceHistory::create([
                'invoice_id' => $invoice->id,
                'user_id' => auth()->id(),
                'action' => 'payment_added',
                'description' => 'Pago registrado por $'.number_format($validated['amount'], 2, ',', '.').'. Estado actual: '.($isPaid ? 'Pagada' : 'Pendiente'),
            ]);

            return (new InvoicePaymentResource($payment))
                ->response()
                ->setStatusCode(201);
        });
    }

    public function show(Invoice $invoice, InvoicePayment $payment)
    {
        $payment->load('paymentMethod');

        return new InvoicePaymentResource($payment);
    }

    public function destroy(Invoice $invoice, InvoicePayment $payment)
    {
        return DB::transaction(function () use ($invoice, $payment) {
            $amount = $payment->amount;
            $payment->delete();

            // Recompute Invoice.status after the deletion: before this fix,
            // deleting a payment from a "paid" invoice silently left it as
            // "paid" with 0 outstanding payments — silent financial drift.
            $invoice = Invoice::lockForUpdate()->findOrFail($invoice->id);
            $totalPaid = $invoice->payments()->sum('amount');
            $isPaid = $invoice->total_amount > 0
                && bccomp($totalPaid, $invoice->total_amount, 2) >= 0;

            $invoice->update([
                'status' => $isPaid ? 'paid' : 'pending',
            ]);

            \App\Models\InvoiceHistory::create([
                'invoice_id' => $invoice->id,
                'user_id' => auth()->id(),
                'action' => 'payment_removed',
                'description' => 'Pago anulado por $'.number_format($amount, 2, ',', '.').'. Estado actual: '.($isPaid ? 'Pagada' : 'Pendiente'),
            ]);

            return response()->json([
                'message' => 'Invoice payment deleted successfully',
            ]);
        });
    }
}
