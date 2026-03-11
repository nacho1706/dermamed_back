<?php

namespace App\Http\Controllers;

use App\Actions\Patient\ImportPatientsAction;
use App\Factories\PatientFactory;
use App\Http\Requests\Patient\ImportPatientRequest;
use App\Http\Requests\Patient\IndexPatientsRequest;
use App\Http\Requests\Patient\StorePatientRequest;
use App\Http\Requests\Patient\UpdatePatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Patient;

class PatientController extends Controller
{
    public function index(IndexPatientsRequest $request)
    {
        $validated = $request->validated();
        $cantidad = $validated['cantidad'] ?? 10;
        $pagina = $validated['pagina'] ?? 1;

        $query = Patient::query();

        if (isset($validated['dni'])) {
            $query->where('dni', $validated['dni']);
        }

        if (isset($validated['first_name'])) {
            $query->where('first_name', 'like', '%'.$validated['first_name'].'%');
        }

        if (isset($validated['last_name'])) {
            $query->where('last_name', 'like', '%'.$validated['last_name'].'%');
        }

        if (isset($validated['cuit'])) {
            $query->where('cuit', 'like', '%'.$validated['cuit'].'%');
        }

        if (isset($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                if (ctype_digit(str_replace(' ', '', $search))) {
                    $q->where('dni', 'ilike', '%'.$search.'%')
                        ->orWhere('phone', 'ilike', '%'.$search.'%');
                } else {
                    $q->where('first_name', 'ilike', '%'.$search.'%')
                        ->orWhere('last_name', 'ilike', '%'.$search.'%')
                        ->orWhere('dni', 'ilike', '%'.$search.'%')
                        ->orWhere('phone', 'ilike', '%'.$search.'%');
                }
            });
        }

        if (isset($validated['insurance_provider'])) {
            $query->where('insurance_provider', 'ilike', '%'.$validated['insurance_provider'].'%');
        }

        if (isset($validated['province'])) {
            $query->where('province', 'ilike', '%'.$validated['province'].'%');
        }

        // Sorting
        $sort = $validated['sort'] ?? '';
        match ($sort) {
            'name_asc'    => $query->orderBy('first_name')->orderBy('last_name'),
            'name_desc'   => $query->orderByDesc('first_name')->orderByDesc('last_name'),
            'created_asc' => $query->orderBy('created_at'),
            default       => $query->orderByDesc('created_at'),
        };

        $paginador = $query->paginate($cantidad, ['*'], 'page', $pagina);

        return PatientResource::collection($paginador);
    }

    public function store(StorePatientRequest $request)
    {
        $validated = $request->validated();

        $patient = PatientFactory::fromRequest($validated);
        $patient->save();

        return (new PatientResource($patient))
            ->response()
            ->setStatusCode(201);
    }

    public function show(\Illuminate\Http\Request $request, Patient $patient)
    {
        if ($request->user()->hasRole('doctor')) {
            $patient->load('medicalRecords');
        }

        return new PatientResource($patient);
    }

    public function update(UpdatePatientRequest $request, Patient $patient)
    {
        $validated = $request->validated();

        $patient = PatientFactory::fromRequest($validated, $patient);
        $patient->save();

        return new PatientResource($patient);
    }

    public function destroy(\Illuminate\Http\Request $request, Patient $patient)
    {
        if (! $request->user()->roles->contains('name', 'clinic_manager')) {
            abort(403, 'Unauthorized action.');
        }

        $patient->delete();

        return response()->json([
            'message' => 'Patient deleted successfully',
        ]);
    }

    public function import(ImportPatientRequest $request, ImportPatientsAction $action)
    {
        $result = $action->execute($request->file('file'));

        return response()->json([
            'message' => $result['message'],
            'imported_count' => $result['imported_count'],
            'errors' => $result['errors'],
        ], $result['status'] === 200 ? 200 : 422);
    }
}
