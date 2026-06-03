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
            'appointment_id' => 'sometimes|nullable|integer|exists:appointments,id',
            'date' => 'sometimes|date',
            'total_amount' => 'sometimes|required|numeric|min:0',
            'status' => ['sometimes', 'required', 'string', \Illuminate\Validation\Rule::in(\App\Enums\InvoiceStatus::values())],
            'cae' => 'sometimes|nullable|string|max:100',

            'items' => 'sometimes|array|min:1',
            'items.*.product_id' => 'nullable|integer|exists:products,id',
            'items.*.service_id' => 'nullable|integer|exists:services,id',
            'items.*.executor_doctor_id' => 'nullable|integer|exists:users,id',
            'items.*.description' => 'nullable|string|max:255',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.unit_price' => 'required_with:items|numeric|min:0',
        ];
    }
}
