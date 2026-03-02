<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'service_id',
        'scheduled_start_at',
        'scheduled_end_at',
        'status',
        'check_in_at',
        'real_start_at',
        'real_end_at',
        'reserve_channel',
        'is_overbook',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_start_at' => 'datetime',
            'scheduled_end_at' => 'datetime',
            'check_in_at' => 'datetime',
            'real_start_at' => 'datetime',
            'real_end_at' => 'datetime',
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        $handleTimestamps = function (Appointment $appointment) {
            if ($appointment->status === 'in_waiting_room') {
                $appointment->check_in_at = $appointment->check_in_at ?? now();
            } elseif ($appointment->status === 'in_progress') {
                $appointment->check_in_at = $appointment->check_in_at ?? now();
                $appointment->real_start_at = $appointment->real_start_at ?? now();
            } elseif ($appointment->status === 'completed') {
                $appointment->check_in_at = $appointment->check_in_at ?? now();
                $appointment->real_start_at = $appointment->real_start_at ?? now();
                $appointment->real_end_at = $appointment->real_end_at ?? now();
            }
        };

        static::updating(function (Appointment $appointment) use ($handleTimestamps) {
            if ($appointment->isDirty('status')) {
                $handleTimestamps($appointment);
            }
        });

        static::creating(function (Appointment $appointment) use ($handleTimestamps) {
            $handleTimestamps($appointment);
        });
    }

    /**
     * Get the patient for this appointment.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the doctor (user) for this appointment.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get the service for this appointment.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the medical record associated with this appointment.
     */
    public function medicalRecord(): HasOne
    {
        return $this->hasOne(MedicalRecord::class);
    }
}
