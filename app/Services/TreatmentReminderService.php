<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\TreatmentReminder;
use App\Models\User;
use Carbon\Carbon;
use InvalidArgumentException;

class TreatmentReminderService
{
    public function __construct(private ClinicSettingService $settings) {}

    /**
     * Crea un reminder automáticamente cuando un turno pasa a 'completed'.
     * Devuelve null si el servicio no tiene followup_default_days configurado.
     */
    public function createFromAppointment(Appointment $appointment, ?int $overrideDays = null, ?string $notes = null): ?TreatmentReminder
    {
        $service = $appointment->service;
        if (! $service) {
            return null;
        }

        $days = $overrideDays ?? $service->followup_default_days;
        if ($days === null || $days <= 0) {
            return null;
        }

        $dueAt = Carbon::now()->addDays($days)->toDateString();
        $anticipation = $service->followup_notify_anticipation_days
            ?? $this->settings->get('treatment_reminders.default_anticipation_days');
        $notifyFromAt = Carbon::parse($dueAt)->subDays($anticipation)->toDateString();

        return TreatmentReminder::create([
            'patient_id' => $appointment->patient_id,
            'appointment_id' => $appointment->id,
            'service_id' => $appointment->service_id,
            'doctor_id' => $appointment->doctor_id,
            'created_by_user_id' => auth()->id(),
            'due_at' => $dueAt,
            'notify_from_at' => $notifyFromAt,
            'status' => TreatmentReminder::STATUS_PENDING,
            'notes' => $notes,
        ]);
    }

    public function markAsContacted(TreatmentReminder $reminder, string $via, ?User $user = null): TreatmentReminder
    {
        if (! in_array($via, TreatmentReminder::CHANNELS, true)) {
            throw new InvalidArgumentException("Canal inválido: {$via}");
        }
        $this->assertTransition($reminder->status, TreatmentReminder::STATUS_CONTACTED);

        $reminder->update([
            'status' => TreatmentReminder::STATUS_CONTACTED,
            'contacted_at' => now(),
            'contacted_via' => $via,
            'contacted_by_user_id' => $user?->id ?? auth()->id(),
        ]);

        return $reminder;
    }

    public function markAsDismissed(TreatmentReminder $reminder, ?string $reason = null, ?User $user = null): TreatmentReminder
    {
        $this->assertTransition($reminder->status, TreatmentReminder::STATUS_DISMISSED);

        $reminder->update([
            'status' => TreatmentReminder::STATUS_DISMISSED,
            'dismissed_reason' => $reason,
            'dismissed_by_user_id' => $user?->id ?? auth()->id(),
        ]);

        return $reminder;
    }

    public function markAsFulfilled(TreatmentReminder $reminder): TreatmentReminder
    {
        $this->assertTransition($reminder->status, TreatmentReminder::STATUS_FULFILLED);
        $reminder->update(['status' => TreatmentReminder::STATUS_FULFILLED]);

        return $reminder;
    }

    public function postpone(TreatmentReminder $reminder, int $days): TreatmentReminder
    {
        if ($days <= 0) {
            throw new InvalidArgumentException('Days must be positive');
        }
        $reminder->update([
            'due_at' => Carbon::parse($reminder->due_at)->addDays($days)->toDateString(),
            'notify_from_at' => Carbon::parse($reminder->notify_from_at)->addDays($days)->toDateString(),
        ]);

        return $reminder;
    }

    /**
     * Resuelve placeholders en un template string.
     */
    public function renderMessage(TreatmentReminder $reminder, string $template): string
    {
        $patient = $reminder->patient;
        $service = $reminder->service;
        $doctor = $reminder->doctor;
        $daysUntilDue = Carbon::now()->diffInDays(Carbon::parse($reminder->due_at), false);

        $vars = [
            '{patient_first_name}' => $patient?->first_name ?? '',
            '{patient_last_name}' => $patient?->last_name ?? '',
            '{patient_full_name}' => trim(($patient?->first_name ?? '').' '.($patient?->last_name ?? '')),
            '{service_name}' => $service?->name ?? 'tu tratamiento',
            '{due_date}' => Carbon::parse($reminder->due_at)->format('d/m/Y'),
            '{days_until_due}' => max(0, (int) $daysUntilDue),
            '{doctor_name}' => $doctor?->name ?? 'tu médico',
        ];

        return strtr($template, $vars);
    }

    /**
     * Valida que la transición de estado sea legal.
     */
    private function assertTransition(string $from, string $to): void
    {
        $allowed = [
            TreatmentReminder::STATUS_PENDING => [
                TreatmentReminder::STATUS_CONTACTED,
                TreatmentReminder::STATUS_DISMISSED,
                TreatmentReminder::STATUS_FULFILLED,
            ],
            TreatmentReminder::STATUS_CONTACTED => [
                TreatmentReminder::STATUS_FULFILLED,
                TreatmentReminder::STATUS_DISMISSED,
            ],
            TreatmentReminder::STATUS_DISMISSED => [
                TreatmentReminder::STATUS_PENDING, // reabrir
            ],
            TreatmentReminder::STATUS_FULFILLED => [],
        ];
        if (! in_array($to, $allowed[$from] ?? [], true)) {
            throw new InvalidArgumentException("Transición inválida: {$from} → {$to}");
        }
    }
}
