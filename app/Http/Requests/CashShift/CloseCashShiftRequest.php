<?php

namespace App\Http\Requests\CashShift;

use Illuminate\Foundation\Http\FormRequest;

class CloseCashShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'closing_balance' => 'required|numeric|min:0',
        ];
    }
}
