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
        $query = Appointment::query()
            ->with(['patient', 'doctor', 'service'])
            ->when(auth()->user()->hasRole('doctor'), fn ($q) => $q->with('medicalRecord'))
            ->when(! auth()->user()->hasRole('doctor'), fn ($q) => $q->with('invoice'));

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
            $query->where('scheduled_start_at', '>=', $validated['date_from']);
        }

        if (isset($validated['date_to'])) {
            $query->where('scheduled_start_at', '<=', $validated['date_to']);
        }

        $paginador = $query->orderBy('scheduled_start_at', 'asc')->paginate($cantidad, ['*'], 'page', $pagina);

        return AppointmentResource::collection($paginador);
    }

    public function store(StoreAppointmentRequest $request)
    {
        $validated = $request->validated();
        $user = auth()->user();

        // Security: Doctors can only schedule for themselves
        if ($user->isDoctor() && ! $user->isClinicManager() && ! $user->isReceptionist()) {
            if (isset($validated['doctor_id']) && (int) $validated['doctor_id'] !== $user->id) {
                return response()->json([
                    'message' => 'No tienes permiso para agendar turnos para otros médicos.',
                ], 403);
            }
            // Ensure they can't bypass by omitting doctor_id (though it's required in request)
            $validated['doctor_id'] = $user->id;
        }

        $appointment = AppointmentFactory::fromRequest($validated);
        $appointment->save();
        $appointment->load(['patient', 'doctor', 'service']);

        return (new AppointmentResource($appointment))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Appointment $appointment)
    {
        $appointment->load(['patient', 'doctor', 'service']);
        
        if (auth()->user()->hasRole('doctor')) {
            $appointment->load('medicalRecord');
        }

        return new AppointmentResource($appointment);
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment)
    {
        $validated = $request->validated();
        $user = auth()->user();

        // Security: Doctors can only modify their own appointments
        if ($user->isDoctor() && ! $user->isClinicManager() && ! $user->isReceptionist()) {
            if ($appointment->doctor_id !== $user->id) {
                return response()->json([
                    'message' => 'No tienes permiso para modificar turnos de otros médicos.',
                ], 403);
            }

            // Also prevent reassigning ownership to someone else
            if (isset($validated['doctor_id']) && (int) $validated['doctor_id'] !== $user->id) {
                return response()->json([
                    'message' => 'No tienes permiso para reasignar este turno a otro médico.',
                ], 403);
            }
        }

        // REGLA 1: Congelar estructura si el turno ya es de un día pasado.
        // startOfDay() nos asegura que durante el día actual aún se puedan corregir horarios.
        if ($appointment->scheduled_start_at->startOfDay()->isPast() && ! $appointment->scheduled_start_at->isToday()) {
            $structuralFields = ['patient_id', 'doctor_id', 'service_id', 'scheduled_start_at', 'scheduled_end_at'];

            foreach ($structuralFields as $field) {
                if (array_key_exists($field, $validated) && $validated[$field] != $appointment->$field) {
                    return response()->json([
                        'message' => 'No puedes modificar datos médicos u horarios de un turno de días anteriores. Solo puedes actualizar su estado o notas.',
                    ], 422);
                }
            }
        }

        // REGLA 2: Máquina de estados estricta (Sin pending ni confirmed)
        if (isset($validated['status']) && $validated['status'] !== $appointment->status) {
            $currentStatus = $appointment->status;
            $newStatus = $validated['status'];

            $allowedTransitions = [
                'scheduled' => ['in_waiting_room', 'in_progress', 'cancelled', 'no_show'],
                'in_waiting_room' => ['in_progress', 'cancelled', 'no_show', 'scheduled'], // Puede irse cansado de esperar + reversión por ingreso accidental
                'in_progress' => ['completed', 'cancelled', 'in_waiting_room', 'scheduled'], // Reversiones por error humano
                'no_show' => ['in_waiting_room', 'scheduled'], // El paciente llegó tarde o reversión.
                'cancelled' => ['scheduled'], // Restaurar turno cancelado
            ];

            // Si el estado actual no está en el array, es un estado final (completed, cancelled, no_show)
            if (! isset($allowedTransitions[$currentStatus]) || ! in_array($newStatus, $allowedTransitions[$currentStatus])) {
                return response()->json([
                    'message' => "La transición de estado de '{$currentStatus}' a '{$newStatus}' no está permitida.",
                ], 422);
            }
        }

        // REGLA 3: Limpieza de métricas al deshacer ingreso a sala de espera
        if (
            isset($validated['status'])
            && in_array($appointment->status, ['in_waiting_room', 'no_show'])
            && $validated['status'] === 'scheduled'
        ) {
            $appointment->check_in_at = null;
        }

        $appointment = AppointmentFactory::fromRequest($validated, $appointment);
        $appointment->save();
        $appointment->load(['patient', 'doctor', 'service']);

        return new AppointmentResource($appointment);
    }

    public function destroy(Appointment $appointment)
    {
        $user = auth()->user();

        // Security: Doctors can only delete their own appointments
        if ($user->isDoctor() && ! $user->isClinicManager() && ! $user->isReceptionist()) {
            if ($appointment->doctor_id !== $user->id) {
                return response()->json([
                    'message' => 'No tienes permiso para eliminar turnos de otros médicos.',
                ], 403);
            }
        }

        $appointment->delete();

        return response()->json([
            'message' => 'Appointment deleted successfully',
        ]);
    }
}
