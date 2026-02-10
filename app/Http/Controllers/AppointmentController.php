<?php

namespace App\Http\Controllers;

use App\Factories\AppointmentFactory;
use App\Http\Requests\Appointment\IndexAppointmentsRequest;
use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Http\Requests\Appointment\UpdateAppointmentRequest;
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

        return response()->json([
            'data' => $paginador->items(),
            'current_page' => $paginador->currentPage(),
            'total_pages' => $paginador->lastPage(),
            'total_registros' => $paginador->total(),
        ]);
    }

    public function store(StoreAppointmentRequest $request)
    {
        $validated = $request->validated();

        $appointment = AppointmentFactory::fromRequest($validated);
        $appointment->save();

        return response()->json([
            'success' => true,
            'message' => 'Appointment created successfully',
            'appointment' => $appointment->load(['patient', 'doctor', 'service']),
        ], 201);
    }

    public function show($id)
    {
        $appointment = Appointment::with(['patient', 'doctor', 'service', 'medicalRecord'])->find($id);

        if (! $appointment) {
            return response()->json([
                'success' => false,
                'message' => 'Appointment not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'appointment' => $appointment,
        ]);
    }

    public function update(UpdateAppointmentRequest $request, $id)
    {
        $appointment = Appointment::find($id);

        if (! $appointment) {
            return response()->json([
                'success' => false,
                'message' => 'Appointment not found',
            ], 404);
        }

        $validated = $request->validated();

        $appointment = AppointmentFactory::fromRequest($validated, $appointment);
        $appointment->save();

        return response()->json([
            'success' => true,
            'message' => 'Appointment updated successfully',
            'appointment' => $appointment->load(['patient', 'doctor', 'service']),
        ]);
    }

    public function destroy($id)
    {
        $appointment = Appointment::find($id);

        if (! $appointment) {
            return response()->json([
                'success' => false,
                'message' => 'Appointment not found',
            ], 404);
        }

        $appointment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Appointment deleted successfully',
        ]);
    }
}
