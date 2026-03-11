<?php

namespace App\Http\Controllers;

use App\Factories\DoctorAvailabilityFactory;
use App\Http\Requests\DoctorAvailability\IndexDoctorAvailabilitiesRequest;
use App\Http\Requests\DoctorAvailability\StoreDoctorAvailabilityRequest;
use App\Http\Requests\DoctorAvailability\SyncDoctorAvailabilitiesRequest;
use App\Http\Requests\DoctorAvailability\UpdateDoctorAvailabilityRequest;
use App\Http\Resources\DoctorAvailabilityResource;
use App\Models\DoctorAvailability;
use Illuminate\Support\Facades\DB;

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

    public function sync(SyncDoctorAvailabilitiesRequest $request)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            // Delete all existing slots for this doctor
            DoctorAvailability::where('doctor_id', $validated['doctor_id'])->delete();

            // Create the new slots
            foreach ($validated['availabilities'] as $slot) {
                DoctorAvailability::create([
                    'doctor_id'    => $validated['doctor_id'],
                    'day_of_week'  => $slot['day_of_week'],
                    'start_time'   => $slot['start_time'],
                    'end_time'     => $slot['end_time'],
                ]);
            }
        });

        // Return the updated list for the doctor
        $availabilities = DoctorAvailability::with('doctor')
            ->where('doctor_id', $validated['doctor_id'])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Disponibilidad actualizada correctamente',
            'data'    => DoctorAvailabilityResource::collection($availabilities),
        ]);
    }

    public function destroy(DoctorAvailability $doctorAvailability)
    {
        $doctorAvailability->delete();

        return response()->json([
            'message' => 'Doctor availability deleted successfully',
        ]);
    }
}
