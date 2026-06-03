<?php

namespace Tests\Feature\Authorization;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesAsRole;
use Tests\TestCase;

class AppointmentIdorTest extends TestCase
{
    use RefreshDatabase, AuthenticatesAsRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    private function makeOtherDoctor(): User
    {
        $other = User::factory()->create();
        $other->roles()->attach(Role::where('name', 'doctor')->first());
        return $other;
    }

    private function makeAppointment(int $doctorId): Appointment
    {
        return Appointment::factory()->create([
            'doctor_id' => $doctorId,
            'patient_id' => Patient::factory()->create()->id,
            'service_id' => Service::query()->first()?->id ?? Service::factory()->create()->id,
        ]);
    }

    public function test_doctor_cannot_view_another_doctors_appointment(): void
    {
        $other = $this->makeOtherDoctor();
        $appt = $this->makeAppointment($other->id);

        $this->withToken($this->tokenFor($this->doctor()))
            ->getJson("/api/appointments/{$appt->id}")
            ->assertStatus(403);
    }

    public function test_doctor_cannot_update_another_doctors_appointment(): void
    {
        $other = $this->makeOtherDoctor();
        $appt = $this->makeAppointment($other->id);

        $this->withToken($this->tokenFor($this->doctor()))
            ->putJson("/api/appointments/{$appt->id}", ['notes' => 'changed by intruder'])
            ->assertStatus(403);
    }

    public function test_doctor_cannot_delete_another_doctors_appointment(): void
    {
        $other = $this->makeOtherDoctor();
        $appt = $this->makeAppointment($other->id);

        $this->withToken($this->tokenFor($this->doctor()))
            ->deleteJson("/api/appointments/{$appt->id}")
            ->assertStatus(403);

        $this->assertNotNull($appt->fresh());
    }

    public function test_doctor_cannot_reassign_appointment_to_himself(): void
    {
        // Doctor B owns the appointment. Doctor A (the authenticated user)
        // tries to update doctor_id and steal it. The policy blocks at
        // view stage, but if it didn't, the controller forbids the change.
        $other = $this->makeOtherDoctor();
        $appt = $this->makeAppointment($other->id);
        $self = $this->doctor();

        $this->withToken($this->tokenFor($self))
            ->putJson("/api/appointments/{$appt->id}", ['doctor_id' => $self->id])
            ->assertStatus(403);

        $this->assertSame($other->id, $appt->fresh()->doctor_id);
    }

    public function test_index_force_scopes_doctor_to_own_appointments(): void
    {
        $other = $this->makeOtherDoctor();
        $self = $this->doctor();

        $foreign = $this->makeAppointment($other->id);
        $own = $this->makeAppointment($self->id);

        $response = $this->withToken($this->tokenFor($self))
            ->getJson("/api/appointments?doctor_id={$other->id}&cantidad=50");

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($own->id, $ids);
        $this->assertNotContains($foreign->id, $ids);
    }

    public function test_receptionist_can_view_all_appointments(): void
    {
        $other = $this->makeOtherDoctor();
        $appt = $this->makeAppointment($other->id);

        $this->withToken($this->tokenFor($this->receptionist()))
            ->getJson("/api/appointments/{$appt->id}")
            ->assertStatus(200);
    }
}
