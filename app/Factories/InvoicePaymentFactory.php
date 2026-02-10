<?php

namespace App\Factories;

use App\Models\InvoicePayment;

class InvoicePaymentFactory
{
    public static function fromRequest($request, ?InvoicePayment $payment = null): InvoicePayment
    {
        $payment = $payment ?? new InvoicePayment();
        $payment->invoice_id = isset($request['invoice_id']) ? $request['invoice_id'] : $payment->invoice_id;
        $payment->payment_method_id = isset($request['payment_method_id']) ? $request['payment_method_id'] : $payment->payment_method_id;
        $payment->amount = isset($request['amount']) ? $request['amount'] : $payment->amount;
        $payment->payment_date = isset($request['payment_date']) ? $request['payment_date'] : $payment->payment_date;

        return $payment;
    }
}
