<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Service;
use App\Models\TreatmentReminder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class TreatmentReminderTest extends TestCase
{
    use RefreshDatabase;

    protected User $receptionist;

    protected User $doctor;

    protected User $manager;

    protected string $tokenReceptionist;

    protected string $tokenDoctor;

    protected string $tokenManager;

    protected Patient $patient;

    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');

        $this->receptionist = User::whereHas('roles', fn ($q) => $q->where('name', 'receptionist'))->firstOrFail();
        $this->doctor = User::whereHas('roles', fn ($q) => $q->where('name', 'doctor'))->firstOrFail();
        $this->manager = User::whereHas('roles', fn ($q) => $q->where('name', 'clinic_manager'))->firstOrFail();

        $this->tokenReceptionist = JWTAuth::fromUser($this->receptionist);
        $this->tokenDoctor = JWTAuth::fromUser($this->doctor);
        $this->tokenManager = JWTAuth::fromUser($this->manager);

        $this->patient = Patient::firstOrFail();
        $this->service = Service::firstOrFail();
    }

    public function test_receptionist_can_create_reminder_manually(): void
    {
        $response = $this->withToken($this->tokenReceptionist)->postJson('/api/treatment-reminders', [
            'patient_id' => $this->patient->id,
            'service_id' => $this->service->id,
            'due_at' => now()->addDays(30)->toDateString(),
            'notes' => 'Control post Botox',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('treatment_reminders', [
            'patient_id' => $this->patient->id,
            'status' => 'pending',
        ]);
    }

    public function test_index_active_returns_reminders_in_window(): void
    {
        TreatmentReminder::create([
            'patient_id' => $this->patient->id,
            'service_id' => $this->service->id,
            'doctor_id' => $this->doctor->id,
            'created_by_user_id' => $this->manager->id,
            'due_at' => now()->addDays(5)->toDateString(),
            'notify_from_at' => now()->subDays(2)->toDateString(),
            'status' => 'pending',
        ]);

        $response = $this->withToken($this->tokenReceptionist)->getJson('/api/treatment-reminders?tab=active');
        $response->assertStatus(200);
        $this->assertGreaterThan(0, count($response->json('data')));
    }

    public function test_can_mark_as_contacted_via_whatsapp(): void
    {
        $reminder = TreatmentReminder::create([
            'patient_id' => $this->patient->id,
            'service_id' => $this->service->id,
            'doctor_id' => $this->doctor->id,
            'created_by_user_id' => $this->manager->id,
            'due_at' => now()->addDays(5)->toDateString(),
            'notify_from_at' => now()->subDays(2)->toDateString(),
            'status' => 'pending',
        ]);

        $response = $this->withToken($this->tokenReceptionist)->patchJson(
            "/api/treatment-reminders/{$reminder->id}",
            ['status' => 'contacted', 'contacted_via' => 'whatsapp'],
        );

        $response->assertStatus(200);
        $this->assertEquals('contacted', $reminder->fresh()->status);
        $this->assertEquals('whatsapp', $reminder->fresh()->contacted_via);
    }

    public function test_dismiss_requires_status_change_and_persists_reason(): void
    {
        $reminder = TreatmentReminder::create([
            'patient_id' => $this->patient->id,
            'due_at' => now()->addDays(5)->toDateString(),
            'notify_from_at' => now()->subDays(2)->toDateString(),
            'status' => 'pending',
            'created_by_user_id' => $this->manager->id,
        ]);

        $response = $this->withToken($this->tokenReceptionist)->patchJson(
            "/api/treatment-reminders/{$reminder->id}",
            ['status' => 'dismissed', 'dismissed_reason' => 'Paciente avisó que no vuelve'],
        );

        $response->assertStatus(200);
        $fresh = $reminder->fresh();
        $this->assertEquals('dismissed', $fresh->status);
        $this->assertEquals('Paciente avisó que no vuelve', $fresh->dismissed_reason);
    }

    public function test_invalid_transition_returns_422(): void
    {
        $reminder = TreatmentReminder::create([
            'patient_id' => $this->patient->id,
            'due_at' => now()->subDays(5)->toDateString(),
            'notify_from_at' => now()->subDays(19)->toDateString(),
            'status' => 'fulfilled',
            'created_by_user_id' => $this->manager->id,
        ]);

        // fulfilled → contacted no debería ser permitido.
        $response = $this->withToken($this->tokenReceptionist)->patchJson(
            "/api/treatment-reminders/{$reminder->id}",
            ['status' => 'contacted', 'contacted_via' => 'whatsapp'],
        );

        $response->assertStatus(422);
    }

    public function test_stats_endpoint(): void
    {
        $response = $this->withToken($this->tokenReceptionist)->getJson('/api/treatment-reminders/stats');
        $response->assertStatus(200);
        $response->assertJsonStructure(['active', 'upcoming']);
    }

    public function test_messages_endpoint_renders_whatsapp_template(): void
    {
        $reminder = TreatmentReminder::create([
            'patient_id' => $this->patient->id,
            'service_id' => $this->service->id,
            'doctor_id' => $this->doctor->id,
            'created_by_user_id' => $this->manager->id,
            'due_at' => now()->addDays(14)->toDateString(),
            'notify_from_at' => now()->toDateString(),
            'status' => 'pending',
        ]);

        $response = $this->withToken($this->tokenReceptionist)->getJson(
            "/api/treatment-reminders/{$reminder->id}/messages?channel=whatsapp",
        );

        $response->assertStatus(200);
        $response->assertJsonStructure(['channel', 'message', 'phone']);
        $this->assertStringNotContainsString('{patient_first_name}', $response->json('message'));
    }

    public function test_observer_creates_reminder_on_appointment_completed(): void
    {
        $this->service->update(['followup_default_days' => 45]);

        $apt = \App\Models\Appointment::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'service_id' => $this->service->id,
            'scheduled_start_at' => now(),
            'scheduled_end_at' => now()->addMinutes(30),
            'status' => 'scheduled',
        ]);

        $apt->update(['status' => 'completed']);

        $this->assertDatabaseHas('treatment_reminders', [
            'appointment_id' => $apt->id,
            'service_id' => $this->service->id,
            'status' => 'pending',
        ]);
    }
}
