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
        $validated['invoice_id'] = $invoice->id;

        $payment = InvoicePaymentFactory::fromRequest($validated);
        $payment->save();
        $payment->load('paymentMethod');

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
