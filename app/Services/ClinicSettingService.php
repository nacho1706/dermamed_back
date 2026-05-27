<?php

namespace App\Services;

use App\Models\ClinicSetting;

class ClinicSettingService
{
    public const DEFAULTS = [
        'treatment_reminders.default_anticipation_days' => 14,
        'treatment_reminders.whatsapp_template' => 'Hola {patient_first_name}, en {days_until_due} días te toca {service_name} con {doctor_name}. ¿Querés agendar un turno?',
        'treatment_reminders.email_subject_template' => 'Recordatorio: control de {service_name}',
        'treatment_reminders.email_body_template' => "Hola {patient_first_name},\n\nEn {days_until_due} días te toca tu control de {service_name} con {doctor_name}.\n\n¿Querés agendar un turno?\n\nSaludos,\nConsultorio DermaMED",
    ];

    public function get(string $key): mixed
    {
        return ClinicSetting::getValue($key, self::DEFAULTS[$key] ?? null);
    }

    public function set(string $key, mixed $value): void
    {
        ClinicSetting::setValue($key, $value);
    }

    /**
     * Devuelve los settings públicos del feature (no incluye secretos).
     */
    public function getPublicSettings(): array
    {
        $keys = array_keys(self::DEFAULTS);
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = $this->get($key);
        }

        return $out;
    }
}
