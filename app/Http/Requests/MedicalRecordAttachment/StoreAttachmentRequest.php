<?php

namespace App\Http\Requests\MedicalRecordAttachment;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attachments' => 'required|array|max:10',
            'attachments.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:8192',
        ];
    }

    public function messages(): array
    {
        return [
            'attachments.required' => 'Debe adjuntar al menos una imagen.',
            'attachments.max' => 'No se pueden subir más de 10 imágenes a la vez.',
            'attachments.*.image' => 'Cada adjunto debe ser una imagen válida.',
            'attachments.*.mimes' => 'Formato permitido: jpg, jpeg, png, webp.',
            'attachments.*.max' => 'Cada imagen no puede superar los 8 MB.',
        ];
    }
}
