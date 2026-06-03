<?php

namespace App\Http\Requests\Brand;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role:clinic_manager already enforced in routes/api.php
    }

    public function rules(): array
    {
        $brand = $this->route('brand');
        $brandId = is_object($brand) ? $brand->id : $brand;

        return [
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique('brands', 'name')->ignore($brandId),
            ],
        ];
    }
}
