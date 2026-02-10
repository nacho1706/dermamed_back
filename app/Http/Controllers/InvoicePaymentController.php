<?php

namespace App\Http\Controllers;

use App\Factories\InvoicePaymentFactory;
use App\Http\Requests\InvoicePayment\StoreInvoicePaymentRequest;
use App\Models\Invoice;
use App\Models\InvoicePayment;

class InvoicePaymentController extends Controller
{
    public function store(StoreInvoicePaymentRequest $request, $invoiceId)
    {
        $invoice = Invoice::find($invoiceId);

        if (! $invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found',
            ], 404);
        }

        $validated = $request->validated();
        $validated['invoice_id'] = $invoice->id;

        $payment = InvoicePaymentFactory::fromRequest($validated);
        $payment->save();

        return response()->json([
            'success' => true,
            'message' => 'Invoice payment created successfully',
            'invoice_payment' => $payment->load('paymentMethod'),
        ], 201);
    }

    public function show($invoiceId, $id)
    {
        $payment = InvoicePayment::with(['invoice', 'paymentMethod'])
            ->where('invoice_id', $invoiceId)
            ->find($id);

        if (! $payment) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice payment not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'invoice_payment' => $payment,
        ]);
    }

    public function destroy($invoiceId, $id)
    {
        $payment = InvoicePayment::where('invoice_id', $invoiceId)->find($id);

        if (! $payment) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice payment not found',
            ], 404);
        }

        $payment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Invoice payment deleted successfully',
        ]);
    }
}
