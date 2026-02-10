<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => 'required|integer|exists:patients,id',
            'voucher_type_id' => 'required|integer|exists:voucher_types,id',
            'date' => 'sometimes|date',
            'total_amount' => 'required|numeric|min:0',
            'status' => 'sometimes|string|in:pending,paid,cancelled',
            'cae' => 'nullable|string|max:100',
        ];
    }
}
