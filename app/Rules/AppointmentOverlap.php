<?php

namespace App\Rules;

use App\Models\Appointment;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

class AppointmentOverlap implements ValidationRule
{
    protected $doctorId;
    protected $startTime;
    protected $endTime;
    protected $ignoreAppointmentId;

    public function __construct($doctorId, $startTime, $endTime, $ignoreAppointmentId = null)
    {
        $this->doctorId = $doctorId;
        $this->startTime = $startTime ? Carbon::parse($startTime) : null;
        $this->endTime = $endTime ? Carbon::parse($endTime) : null;
        $this->ignoreAppointmentId = $ignoreAppointmentId;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->doctorId || !$this->startTime || !$this->endTime) {
            return; // Other validation rules will catch missing fields
        }

        $query = Appointment::query()
            ->where('doctor_id', $this->doctorId)
            ->whereNotIn('status', ['cancelled', 'completed', 'no_show']) // Ignore inactive statuses
            ->where(function ($q) {
                // Check for overlap
                $q->where(function ($q) {
                    // New start inside existing key
                    $q->where('start_time', '<', $this->endTime)
                      ->where('end_time', '>', $this->startTime);
                });
            });

        if ($this->ignoreAppointmentId) {
            $query->where('id', '!=', $this->ignoreAppointmentId);
        }

        if ($query->exists()) {
            $fail('El horario seleccionado se superpone con otro turno existente para este doctor.');
        }
    }
}
