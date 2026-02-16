<?php

namespace App\Http\Controllers;

use App\Factories\AppointmentFactory;
use App\Http\Requests\Appointment\IndexAppointmentsRequest;
use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Http\Requests\Appointment\UpdateAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;

class AppointmentController extends Controller
{
    public function index(IndexAppointmentsRequest $request)
    {
        $validated = $request->validated();
        $cantidad = $validated['cantidad'] ?? 10;
        $pagina = $validated['pagina'] ?? 1;

        $query = Appointment::query()->with(['patient', 'doctor', 'service']);

        if (isset($validated['patient_id'])) {
            $query->where('patient_id', $validated['patient_id']);
        }

        if (isset($validated['doctor_id'])) {
            $query->where('doctor_id', $validated['doctor_id']);
        }

        if (isset($validated['service_id'])) {
            $query->where('service_id', $validated['service_id']);
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['date_from'])) {
            $query->where('start_time', '>=', $validated['date_from']);
        }

        if (isset($validated['date_to'])) {
            $query->where('start_time', '<=', $validated['date_to']);
        }

        $paginador = $query->orderBy('start_time', 'asc')->paginate($cantidad, ['*'], 'page', $pagina);

        return AppointmentResource::collection($paginador);
    }

    public function store(StoreAppointmentRequest $request)
    {
        $validated = $request->validated();

        $appointment = AppointmentFactory::fromRequest($validated);
        $appointment->save();
        $appointment->load(['patient', 'doctor', 'service']);

        return (new AppointmentResource($appointment))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Appointment $appointment)
    {
        $appointment->load(['patient', 'doctor', 'service', 'medicalRecord']);

        return new AppointmentResource($appointment);
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment)
    {
        $validated = $request->validated();

        $appointment = AppointmentFactory::fromRequest($validated, $appointment);
        $appointment->save();
        $appointment->load(['patient', 'doctor', 'service']);

        return new AppointmentResource($appointment);
    }

    public function destroy(Appointment $appointment)
    {
        $appointment->delete();

        return response()->json([
            'message' => 'Appointment deleted successfully',
        ]);
    }
}
