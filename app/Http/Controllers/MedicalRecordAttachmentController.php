<?php

namespace App\Http\Controllers;

use App\Factories\MedicalRecordAttachmentFactory;
use App\Http\Requests\MedicalRecordAttachment\StoreAttachmentRequest;
use App\Models\MedicalRecord;
use App\Models\MedicalRecordAttachment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MedicalRecordAttachmentController extends Controller
{
    /**
     * POST /medical-records/{medical_record}/attachments
     * Subir una o más imágenes adjuntas a un registro médico.
     * Middleware: auth:api + role:doctor
     */
    public function store(StoreAttachmentRequest $request, MedicalRecord $medicalRecord)
    {
        $uploaded = [];

        foreach ($request->file('attachments') as $file) {
            $extension = $file->getClientOriginalExtension();
            $uuid = Str::uuid()->toString();
            $fileName = "{$uuid}.{$extension}";
            $path = "private/medical_records/{$medicalRecord->id}/{$fileName}";

            Storage::disk('local')->putFileAs(
                "private/medical_records/{$medicalRecord->id}",
                $file,
                $fileName
            );

            $attachment = MedicalRecordAttachmentFactory::fromValidated([
                'medical_record_id' => $medicalRecord->id,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);

            $attachment->save();
            $uploaded[] = $attachment;
        }

        return response()->json([
            'success' => true,
            'message' => count($uploaded).' adjunto(s) guardado(s) correctamente.',
            'attachments' => $uploaded,
        ], 201);
    }

    /**
     * GET /medical-records/{medical_record}/attachments/{attachment}
     * Servir el archivo inline para que el navegador lo renderice.
     * Middleware: auth:api (solo autenticado, no restringido a doctor).
     */
    public function show(MedicalRecord $medicalRecord, MedicalRecordAttachment $attachment)
    {
        // Verificar que el attachment pertenece al medical record (evitar IDOR)
        abort_if(
            $attachment->medical_record_id !== $medicalRecord->id,
            404,
            'Adjunto no encontrado.'
        );

        $fullPath = Storage::disk('local')->path($attachment->path);

        abort_if(! file_exists($fullPath), 404, 'Archivo no encontrado en el almacenamiento.');

        // response()->file() envía Content-Disposition: inline
        // El navegador renderiza la imagen directamente (no fuerza descarga)
        return response()->file($fullPath, [
            'Content-Type' => $attachment->mime_type,
        ]);
    }

    /**
     * DELETE /medical-records/{medical_record}/attachments/{attachment}
     * Eliminar un adjunto del disco y de la base de datos.
     * Middleware: auth:api + role:doctor
     */
    public function destroy(MedicalRecord $medicalRecord, MedicalRecordAttachment $attachment)
    {
        abort_if(
            $attachment->medical_record_id !== $medicalRecord->id,
            404,
            'Adjunto no encontrado.'
        );

        Storage::disk('local')->delete($attachment->path);
        $attachment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Adjunto eliminado correctamente.',
        ]);
    }
}
