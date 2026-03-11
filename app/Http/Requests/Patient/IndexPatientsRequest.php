<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;

class IndexPatientsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cantidad'          => 'sometimes|integer|min:1',
            'pagina'            => 'sometimes|integer|min:1',
            'first_name'        => 'sometimes|string',
            'last_name'         => 'sometimes|string',
            'dni'               => 'sometimes|string',
            'cuit'              => 'sometimes|string',
            'search'            => 'sometimes|string',
            'insurance_provider'=> 'sometimes|string',
            'province'          => 'sometimes|string',
            'sort'              => 'sometimes|string|in:name_asc,name_desc,created_asc',
        ];
    }
}
