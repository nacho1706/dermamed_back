<?php

namespace App\Http\Requests\CashShift;

use Illuminate\Foundation\Http\FormRequest;

class OpenCashShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'opening_balance' => 'required|numeric|min:0',
        ];
    }
}
