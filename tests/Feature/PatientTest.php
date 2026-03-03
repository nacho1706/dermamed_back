<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class PatientTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed');

        $this->user = User::where('email', 'director@dermamed.com')->first();
        $this->token = JWTAuth::fromUser($this->user);
    }

    // ── 1. Happy path ───────────────────────────────────────────────────────

    public function test_can_create_patient_with_valid_dni(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/patients', [
            'first_name' => 'Ana',
            'last_name' => 'García',
            'dni' => '12345678',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('patients', ['dni' => '12345678']);
    }

    // ── 2. DNI required ─────────────────────────────────────────────────────

    public function test_cannot_create_patient_without_dni(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/patients', [
            'first_name' => 'Sin',
            'last_name' => 'DNI',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['dni']);
    }

    // ── 3. DNI format ───────────────────────────────────────────────────────

    public function test_cannot_create_patient_with_invalid_dni_format(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/patients', [
            'first_name' => 'Ana',
            'last_name' => 'García',
            'dni' => 'ABC123', // letters not allowed
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['dni']);
    }

    // ── 4. Duplicate DNI → 422 ──────────────────────────────────────────────

    public function test_duplicate_dni_returns_422(): void
    {
        Patient::factory()->create(['dni' => '99887766']);

        $response = $this->withToken($this->token)->postJson('/api/patients', [
            'first_name' => 'Otro',
            'last_name' => 'Paciente',
            'dni' => '99887766',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['dni']);
    }
}
