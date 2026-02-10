<?php

namespace App\Http\Controllers;

use App\Factories\DoctorAvailabilityFactory;
use App\Http\Requests\DoctorAvailability\IndexDoctorAvailabilitiesRequest;
use App\Http\Requests\DoctorAvailability\StoreDoctorAvailabilityRequest;
use App\Http\Requests\DoctorAvailability\UpdateDoctorAvailabilityRequest;
use App\Models\DoctorAvailability;

class DoctorAvailabilityController extends Controller
{
    public function index(IndexDoctorAvailabilitiesRequest $request)
    {
        $validated = $request->validated();
        $cantidad = $validated['cantidad'] ?? 10;
        $pagina = $validated['pagina'] ?? 1;

        $query = DoctorAvailability::query()->with('doctor');

        if (isset($validated['doctor_id'])) {
            $query->where('doctor_id', $validated['doctor_id']);
        }

        $paginador = $query->orderBy('day_of_week')->orderBy('start_time')->paginate($cantidad, ['*'], 'page', $pagina);

        return response()->json([
            'data' => $paginador->items(),
            'current_page' => $paginador->currentPage(),
            'total_pages' => $paginador->lastPage(),
            'total_registros' => $paginador->total(),
        ]);
    }

    public function store(StoreDoctorAvailabilityRequest $request)
    {
        $validated = $request->validated();

        $availability = DoctorAvailabilityFactory::fromRequest($validated);
        $availability->save();

        return response()->json([
            'success' => true,
            'message' => 'Doctor availability created successfully',
            'doctor_availability' => $availability->load('doctor'),
        ], 201);
    }

    public function show($id)
    {
        $availability = DoctorAvailability::with('doctor')->find($id);

        if (! $availability) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor availability not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'doctor_availability' => $availability,
        ]);
    }

    public function update(UpdateDoctorAvailabilityRequest $request, $id)
    {
        $availability = DoctorAvailability::find($id);

        if (! $availability) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor availability not found',
            ], 404);
        }

        $validated = $request->validated();

        $availability = DoctorAvailabilityFactory::fromRequest($validated, $availability);
        $availability->save();

        return response()->json([
            'success' => true,
            'message' => 'Doctor availability updated successfully',
            'doctor_availability' => $availability->load('doctor'),
        ]);
    }

    public function destroy($id)
    {
        $availability = DoctorAvailability::find($id);

        if (! $availability) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor availability not found',
            ], 404);
        }

        $availability->delete();

        return response()->json([
            'success' => true,
            'message' => 'Doctor availability deleted successfully',
        ]);
    }
}
