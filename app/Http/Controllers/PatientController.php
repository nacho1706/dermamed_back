<?php

namespace App\Http\Controllers;

use App\Factories\PatientFactory;
use App\Http\Requests\Patient\IndexPatientsRequest;
use App\Http\Requests\Patient\StorePatientRequest;
use App\Http\Requests\Patient\UpdatePatientRequest;
use App\Models\Patient;

class PatientController extends Controller
{
    public function index(IndexPatientsRequest $request)
    {
        $validated = $request->validated();
        $cantidad = $validated['cantidad'] ?? 10;
        $pagina = $validated['pagina'] ?? 1;

        $query = Patient::query();

        if (isset($validated['first_name'])) {
            $query->where('first_name', 'like', '%' . $validated['first_name'] . '%');
        }

        if (isset($validated['last_name'])) {
            $query->where('last_name', 'like', '%' . $validated['last_name'] . '%');
        }

        if (isset($validated['cuit'])) {
            $query->where('cuit', 'like', '%' . $validated['cuit'] . '%');
        }

        $paginador = $query->paginate($cantidad, ['*'], 'page', $pagina);

        return response()->json([
            'data' => $paginador->items(),
            'current_page' => $paginador->currentPage(),
            'total_pages' => $paginador->lastPage(),
            'total_registros' => $paginador->total(),
        ]);
    }

    public function store(StorePatientRequest $request)
    {
        $validated = $request->validated();

        $patient = PatientFactory::fromRequest($validated);
        $patient->save();

        return response()->json([
            'success' => true,
            'message' => 'Patient created successfully',
            'patient' => $patient,
        ], 201);
    }

    public function show($id)
    {
        $patient = Patient::find($id);

        if (! $patient) {
            return response()->json([
                'success' => false,
                'message' => 'Patient not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'patient' => $patient,
        ]);
    }

    public function update(UpdatePatientRequest $request, $id)
    {
        $patient = Patient::find($id);

        if (! $patient) {
            return response()->json([
                'success' => false,
                'message' => 'Patient not found',
            ], 404);
        }

        $validated = $request->validated();

        $patient = PatientFactory::fromRequest($validated, $patient);
        $patient->save();

        return response()->json([
            'success' => true,
            'message' => 'Patient updated successfully',
            'patient' => $patient,
        ]);
    }

    public function destroy($id)
    {
        $patient = Patient::find($id);

        if (! $patient) {
            return response()->json([
                'success' => false,
                'message' => 'Patient not found',
            ], 404);
        }

        $patient->delete();

        return response()->json([
            'success' => true,
            'message' => 'Patient deleted successfully',
        ]);
    }
}
