<?php

namespace App\Http\Controllers;

use App\Factories\MedicalRecordFactory;
use App\Http\Requests\MedicalRecord\IndexMedicalRecordsRequest;
use App\Http\Requests\MedicalRecord\StoreMedicalRecordRequest;
use App\Http\Requests\MedicalRecord\UpdateMedicalRecordRequest;
use App\Http\Resources\MedicalRecordResource;
use App\Models\MedicalRecord;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class MedicalRecordController extends Controller
{
    public function index(IndexMedicalRecordsRequest $request)
    {
        if (! auth()->user()->hasRole('doctor')) {
            abort(403, 'Access denied: Only doctors can view medical records list.');
        }
        
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

        return MedicalRecordResource::collection($paginador);
    }

    public function store(StoreMedicalRecordRequest $request)
    {
        $validated = $request->validated();
        $supplies  = $validated['supplies'] ?? [];

        $record = DB::transaction(function () use ($validated, $supplies) {
            // Build the record, storing supplies snapshot in JSONB
            $data = $validated;
            if (! empty($supplies)) {
                $data['supplies_used'] = $supplies;
            }
            unset($data['supplies']); // not a DB column

            $record = MedicalRecordFactory::fromRequest($data);
            $record->save();

            // Create stock movements (type = 'out') for each consumed supply
            foreach ($supplies as $supply) {
                StockMovement::create([
                    'product_id' => $supply['product_id'],
                    'user_id'    => auth()->id(),
                    'type'       => 'out',
                    'quantity'   => $supply['quantity'],
                    'reason'     => "Consumo en consulta médica #{$record->id}",
                ]);
            }

            return $record;
        });

        $record->load(['patient', 'doctor', 'appointment']);

        return (new MedicalRecordResource($record))
            ->response()
            ->setStatusCode(201);
    }

    public function show(MedicalRecord $medicalRecord)
    {
        $medicalRecord->load(['patient', 'doctor', 'appointment']);

        return new MedicalRecordResource($medicalRecord);
    }

    public function update(UpdateMedicalRecordRequest $request, MedicalRecord $medicalRecord)
    {
        $validated = $request->validated();

        $medicalRecord = MedicalRecordFactory::fromRequest($validated, $medicalRecord);
        $medicalRecord->save();
        $medicalRecord->load(['patient', 'doctor', 'appointment']);

        return new MedicalRecordResource($medicalRecord);
    }

    public function destroy(MedicalRecord $medicalRecord)
    {
        $medicalRecord->delete();

        return response()->json([
            'message' => 'Medical record deleted successfully',
        ]);
    }
}

