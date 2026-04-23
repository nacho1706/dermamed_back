<?php

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Appointment $appointment) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('appointments'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'appointment.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'id'            => $this->appointment->id,
            'status'        => $this->appointment->status,
            'patient_id'    => $this->appointment->patient_id,
            'doctor_id'     => $this->appointment->doctor_id,
            'check_in_at'   => $this->appointment->check_in_at?->toIso8601String(),
            'real_start_at' => $this->appointment->real_start_at?->toIso8601String(),
            'real_end_at'   => $this->appointment->real_end_at?->toIso8601String(),
            'updated_at'    => $this->appointment->updated_at->toIso8601String(),
        ];
    }
}
