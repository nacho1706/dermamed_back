<?php

namespace Tests\Feature\Authorization;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesAsRole;
use Tests\TestCase;

/**
 * Appointment state machine — enforced in AppointmentController::update.
 * Mirrors the matrix declared in AppointmentStatus::allowedNext().
 */
class AppointmentTransitionTest extends TestCase
{
    use RefreshDatabase, AuthenticatesAsRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    private function makeAppointment(string $status = 'scheduled'): Appointment
    {
        return Appointment::factory()->create([
            'doctor_id' => $this->doctor()->id,
            'patient_id' => Patient::factory()->create()->id,
            'service_id' => Service::query()->first()?->id ?? Service::factory()->create()->id,
            'status' => $status,
        ]);
    }

    public function test_scheduled_to_in_waiting_room_is_allowed(): void
    {
        $appt = $this->makeAppointment('scheduled');

        $this->withToken($this->tokenFor($this->receptionist()))
            ->putJson("/api/appointments/{$appt->id}", ['status' => 'in_waiting_room'])
            ->assertStatus(200);
    }

    public function test_scheduled_to_completed_directly_is_blocked(): void
    {
        $appt = $this->makeAppointment('scheduled');

        $this->withToken($this->tokenFor($this->doctor()))
            ->putJson("/api/appointments/{$appt->id}", ['status' => 'completed'])
            ->assertStatus(422);

        $this->assertSame('scheduled', $appt->fresh()->status);
    }

    public function test_in_progress_to_completed_is_allowed(): void
    {
        $appt = $this->makeAppointment('in_progress');

        $this->withToken($this->tokenFor($this->doctor()))
            ->putJson("/api/appointments/{$appt->id}", ['status' => 'completed'])
            ->assertStatus(200);

        $this->assertSame('completed', $appt->fresh()->status);
    }

    public function test_completed_cannot_be_reversed(): void
    {
        $appt = $this->makeAppointment('completed');

        $this->withToken($this->tokenFor($this->doctor()))
            ->putJson("/api/appointments/{$appt->id}", ['status' => 'in_progress'])
            ->assertStatus(422);
    }

    public function test_cancelled_can_be_restored_to_scheduled(): void
    {
        $appt = $this->makeAppointment('cancelled');

        $this->withToken($this->tokenFor($this->receptionist()))
            ->putJson("/api/appointments/{$appt->id}", ['status' => 'scheduled'])
            ->assertStatus(200);
    }

    public function test_unknown_status_is_rejected_by_validation(): void
    {
        $appt = $this->makeAppointment('scheduled');

        $this->withToken($this->tokenFor($this->receptionist()))
            ->putJson("/api/appointments/{$appt->id}", ['status' => 'pending'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }
}
