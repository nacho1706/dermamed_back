<?php

namespace App\Http\Requests\Brand;

use Illuminate\Foundation\Http\FormRequest;

class StoreBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role:clinic_manager already enforced in routes/api.php
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:150|unique:brands,name',
        ];
    }
}
