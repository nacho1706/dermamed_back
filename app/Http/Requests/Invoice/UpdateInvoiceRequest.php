<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => 'sometimes|required|integer|exists:patients,id',
            'voucher_type_id' => 'sometimes|required|integer|exists:voucher_types,id',
            'date' => 'sometimes|date',
            'total_amount' => 'sometimes|required|numeric|min:0',
            'status' => 'sometimes|required|string|in:pending,paid,cancelled',
            'cae' => 'sometimes|nullable|string|max:100',
        ];
    }
}
