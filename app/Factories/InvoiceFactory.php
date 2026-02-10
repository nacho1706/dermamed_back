<?php

namespace App\Factories;

use App\Models\Invoice;

class InvoiceFactory
{
    public static function fromRequest($request, ?Invoice $invoice = null): Invoice
    {
        $invoice = $invoice ?? new Invoice();
        $invoice->patient_id = isset($request['patient_id']) ? $request['patient_id'] : $invoice->patient_id;
        $invoice->voucher_type_id = isset($request['voucher_type_id']) ? $request['voucher_type_id'] : $invoice->voucher_type_id;
        $invoice->date = isset($request['date']) ? $request['date'] : $invoice->date;
        $invoice->total_amount = isset($request['total_amount']) ? $request['total_amount'] : $invoice->total_amount;
        $invoice->status = isset($request['status']) ? $request['status'] : $invoice->status;
        $invoice->cae = isset($request['cae']) ? $request['cae'] : $invoice->cae;

        return $invoice;
    }
}
