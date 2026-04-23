<?php

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentCreated implements ShouldBroadcast
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
        return 'appointment.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id'        => $this->appointment->id,
            'doctor_id' => $this->appointment->doctor_id,
            'date'      => $this->appointment->scheduled_start_at->toDateString(),
        ];
    }
}
