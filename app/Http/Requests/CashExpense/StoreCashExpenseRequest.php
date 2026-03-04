<?php

namespace App\Http\Requests\CashExpense;

use Illuminate\Foundation\Http\FormRequest;

class StoreCashExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount'        => ['required', 'numeric', 'min:1'],
            'description'   => ['required', 'string', 'max:255'],
            'cash_shift_id' => ['required', 'integer', 'exists:cash_shifts,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required'        => 'El monto es obligatorio.',
            'amount.numeric'         => 'El monto debe ser un número.',
            'amount.min'             => 'El monto mínimo es $1.',
            'description.required'   => 'La descripción es obligatoria.',
            'description.max'        => 'La descripción no puede superar los 255 caracteres.',
            'cash_shift_id.required' => 'El turno de caja es obligatorio.',
            'cash_shift_id.exists'   => 'El turno de caja seleccionado no existe.',
        ];
    }
}
