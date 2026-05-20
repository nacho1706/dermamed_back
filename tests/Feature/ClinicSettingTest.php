<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ClinicSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_settings(): void
    {
        $this->artisan('db:seed');
        $receptionist = User::whereHas('roles', fn ($q) => $q->where('name', 'receptionist'))->firstOrFail();
        $token = JWTAuth::fromUser($receptionist);

        $response = $this->withToken($token)->getJson('/api/clinic-settings');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'treatment_reminders.default_anticipation_days',
            'treatment_reminders.whatsapp_template',
            'treatment_reminders.email_subject_template',
            'treatment_reminders.email_body_template',
        ]);
    }

    public function test_receptionist_cannot_update_settings(): void
    {
        $this->artisan('db:seed');
        $receptionist = User::whereHas('roles', fn ($q) => $q->where('name', 'receptionist'))->firstOrFail();
        $token = JWTAuth::fromUser($receptionist);

        $response = $this->withToken($token)->patchJson('/api/clinic-settings', [
            'settings' => ['treatment_reminders.default_anticipation_days' => 21],
        ]);

        $response->assertStatus(403);
    }

    public function test_clinic_manager_can_update_settings(): void
    {
        $this->artisan('db:seed');
        $manager = User::whereHas('roles', fn ($q) => $q->where('name', 'clinic_manager'))->firstOrFail();
        $token = JWTAuth::fromUser($manager);

        $response = $this->withToken($token)->patchJson('/api/clinic-settings', [
            'settings' => ['treatment_reminders.default_anticipation_days' => 21],
        ]);

        $response->assertStatus(200);
        $body = $response->json();
        $this->assertEquals(21, $body['treatment_reminders.default_anticipation_days']);
    }
}
