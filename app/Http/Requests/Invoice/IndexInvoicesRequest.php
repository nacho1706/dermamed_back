<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class IndexInvoicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cantidad' => 'sometimes|integer|min:1',
            'pagina' => 'sometimes|integer|min:1',
            'patient_id' => 'sometimes|integer|exists:patients,id',
            'status' => 'sometimes|string|in:pending,paid,cancelled',
        ];
    }
}
