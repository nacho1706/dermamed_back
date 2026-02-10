<?php

namespace App\Http\Controllers;

use App\Factories\MedicalRecordFactory;
use App\Http\Requests\MedicalRecord\IndexMedicalRecordsRequest;
use App\Http\Requests\MedicalRecord\StoreMedicalRecordRequest;
use App\Http\Requests\MedicalRecord\UpdateMedicalRecordRequest;
use App\Models\MedicalRecord;

class MedicalRecordController extends Controller
{
    public function index(IndexMedicalRecordsRequest $request)
    {
        $validated = $request->validated();
        $cantidad = $validated['cantidad'] ?? 10;
        $pagina = $validated['pagina'] ?? 1;

        $query = MedicalRecord::query()->with(['patient', 'doctor', 'appointment']);

        if (isset($validated['patient_id'])) {
            $query->where('patient_id', $validated['patient_id']);
        }

        if (isset($validated['doctor_id'])) {
            $query->where('doctor_id', $validated['doctor_id']);
        }

        $paginador = $query->orderBy('date', 'desc')->paginate($cantidad, ['*'], 'page', $pagina);

        return response()->json([
            'data' => $paginador->items(),
            'current_page' => $paginador->currentPage(),
            'total_pages' => $paginador->lastPage(),
            'total_registros' => $paginador->total(),
        ]);
    }

    public function store(StoreMedicalRecordRequest $request)
    {
        $validated = $request->validated();

        $record = MedicalRecordFactory::fromRequest($validated);
        $record->save();

        return response()->json([
            'success' => true,
            'message' => 'Medical record created successfully',
            'medical_record' => $record->load(['patient', 'doctor', 'appointment']),
        ], 201);
    }

    public function show($id)
    {
        $record = MedicalRecord::with(['patient', 'doctor', 'appointment'])->find($id);

        if (! $record) {
            return response()->json([
                'success' => false,
                'message' => 'Medical record not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'medical_record' => $record,
        ]);
    }

    public function update(UpdateMedicalRecordRequest $request, $id)
    {
        $record = MedicalRecord::find($id);

        if (! $record) {
            return response()->json([
                'success' => false,
                'message' => 'Medical record not found',
            ], 404);
        }

        $validated = $request->validated();

        $record = MedicalRecordFactory::fromRequest($validated, $record);
        $record->save();

        return response()->json([
            'success' => true,
            'message' => 'Medical record updated successfully',
            'medical_record' => $record->load(['patient', 'doctor', 'appointment']),
        ]);
    }

    public function destroy($id)
    {
        $record = MedicalRecord::find($id);

        if (! $record) {
            return response()->json([
                'success' => false,
                'message' => 'Medical record not found',
            ], 404);
        }

        $record->delete();

        return response()->json([
            'success' => true,
            'message' => 'Medical record deleted successfully',
        ]);
    }
}
