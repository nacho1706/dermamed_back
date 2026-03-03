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
            // ── Invoice header ───────────────────────────────────────────────
            'patient_id' => 'required|integer|exists:patients,id',
            'voucher_type_id' => 'nullable|integer|exists:voucher_types,id',
            'appointment_id' => 'nullable|integer|exists:appointments,id',
            'date' => 'nullable|date',
            'cae' => 'nullable|string|max:100',

            // ── Items (at least one) ─────────────────────────────────────────
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|integer|exists:products,id|required_without:items.*.service_id',
            'items.*.service_id' => 'nullable|integer|exists:services,id|required_without:items.*.product_id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.description' => 'nullable|string|max:255',
            'items.*.executor_doctor_id' => 'nullable|integer|exists:users,id',

            // ── Payments ──────────────────────────────────────────────────────
            'payments' => 'nullable|array',
            'payments.*.payment_method_id' => 'required|integer|exists:payment_methods,id',
            'payments.*.amount' => 'required|numeric|min:0.01',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Debe incluir al menos un ítem en la factura.',
            'items.min' => 'Debe incluir al menos un ítem en la factura.',
            'items.*.product_id.required_without' => 'Cada ítem debe tener un producto o un servicio.',
            'items.*.service_id.required_without' => 'Cada ítem debe tener un servicio o un producto.',
            'items.*.quantity.required' => 'La cantidad es requerida para cada ítem.',
            'items.*.quantity.min' => 'La cantidad debe ser al menos 1.',
            'payments.required' => 'Debe incluir al menos un dato de pago (puede estar vacío pero el campo debe existir como array).',
            'payments.*.payment_method_id.required' => 'El método de pago es requerido.',
        ];
    }
}
