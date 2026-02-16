<?php

namespace App\Http\Controllers;

use App\Factories\DoctorAvailabilityFactory;
use App\Http\Requests\DoctorAvailability\IndexDoctorAvailabilitiesRequest;
use App\Http\Requests\DoctorAvailability\StoreDoctorAvailabilityRequest;
use App\Http\Requests\DoctorAvailability\UpdateDoctorAvailabilityRequest;
use App\Http\Resources\DoctorAvailabilityResource;
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

        return DoctorAvailabilityResource::collection($paginador);
    }

    public function store(StoreDoctorAvailabilityRequest $request)
    {
        $validated = $request->validated();

        $availability = DoctorAvailabilityFactory::fromRequest($validated);
        $availability->save();
        $availability->load('doctor');

        return (new DoctorAvailabilityResource($availability))
            ->response()
            ->setStatusCode(201);
    }

    public function show(DoctorAvailability $doctorAvailability)
    {
        $doctorAvailability->load('doctor');

        return new DoctorAvailabilityResource($doctorAvailability);
    }

    public function update(UpdateDoctorAvailabilityRequest $request, DoctorAvailability $doctorAvailability)
    {
        $validated = $request->validated();

        $doctorAvailability = DoctorAvailabilityFactory::fromRequest($validated, $doctorAvailability);
        $doctorAvailability->save();
        $doctorAvailability->load('doctor');

        return new DoctorAvailabilityResource($doctorAvailability);
    }

    public function destroy(DoctorAvailability $doctorAvailability)
    {
        $doctorAvailability->delete();

        return response()->json([
            'message' => 'Doctor availability deleted successfully',
        ]);
    }
}
