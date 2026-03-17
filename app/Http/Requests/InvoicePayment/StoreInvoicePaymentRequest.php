<?php

namespace App\Http\Requests\InvoicePayment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreInvoicePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method_id' => 'required|integer|exists:payment_methods,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'sometimes|date',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var \App\Models\Invoice $invoice */
            $invoice = $this->route('invoice');

            if (! $invoice) {
                return;
            }

            $totalPaid = (float) $invoice->payments()->sum('amount');
            $remaining = (float) bcsub((string) $invoice->total_amount, (string) $totalPaid, 2);

            if ($remaining <= 0) {
                $validator->errors()->add(
                    'amount',
                    'La factura ya está pagada en su totalidad.'
                );

                return;
            }

            if ((float) $this->input('amount') > $remaining) {
                $validator->errors()->add(
                    'amount',
                    "El monto supera el saldo pendiente de la factura ($ {$remaining})."
                );
            }
        });
    }
}
