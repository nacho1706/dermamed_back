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
        $this->authorize('viewAny', MedicalRecord::class);

        $user = auth()->user();
        $validated = $request->validated();
        $cantidad = $validated['cantidad'] ?? 10;
        $pagina = $validated['pagina'] ?? 1;

        $query = MedicalRecord::query()->with(['patient', 'doctor', 'appointment'])->withCount('attachments');

        // Doctors can only see their own records, regardless of the doctor_id filter.
        if ($user->isDoctor() && ! $user->isClinicManager()) {
            $query->where('doctor_id', $user->id);
        } elseif (isset($validated['doctor_id'])) {
            $query->where('doctor_id', $validated['doctor_id']);
        }

        if (isset($validated['patient_id'])) {
            $query->where('patient_id', $validated['patient_id']);
        }

        $paginador = $query->orderBy('date', 'desc')->paginate($cantidad, ['*'], 'page', $pagina);

        return MedicalRecordResource::collection($paginador);
    }

    public function store(StoreMedicalRecordRequest $request)
    {
        $this->authorize('create', MedicalRecord::class);

        $validated = $request->validated();
        $supplies = $validated['supplies'] ?? [];

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
                    'user_id' => auth()->id(),
                    'type' => 'out',
                    'quantity' => $supply['quantity'],
                    'reason' => "Consumo en consulta médica #{$record->id}",
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
        $this->authorize('view', $medicalRecord);

        $medicalRecord->load(['patient', 'doctor', 'appointment', 'attachments']);

        return new MedicalRecordResource($medicalRecord);
    }

    public function update(UpdateMedicalRecordRequest $request, MedicalRecord $medicalRecord)
    {
        $this->authorize('update', $medicalRecord);

        $validated = $request->validated();

        $medicalRecord = MedicalRecordFactory::fromRequest($validated, $medicalRecord);
        $medicalRecord->save();
        $medicalRecord->load(['patient', 'doctor', 'appointment']);

        return new MedicalRecordResource($medicalRecord);
    }

    public function destroy(MedicalRecord $medicalRecord)
    {
        $this->authorize('delete', $medicalRecord);

        $medicalRecord->delete();

        return response()->json([
            'message' => 'Medical record deleted successfully',
        ]);
    }
}
