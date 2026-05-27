<?php

namespace Tests\Feature;

use App\Models\DoctorAvailability;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class DoctorAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected $doctor;

    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed');

        // Get a doctor from the seeders
        $this->doctor = User::whereHas('roles', function ($query) {
            $query->where('name', 'doctor');
        })->first();

        $this->token = JWTAuth::fromUser($this->doctor);
    }

    public function test_can_sync_multiple_availabilities_for_same_day(): void
    {
        // Define multiple slots for Monday (1)
        $availabilities = [
            [
                'day_of_week' => 1,
                'start_time' => '08:00',
                'end_time' => '13:00',
            ],
            [
                'day_of_week' => 1,
                'start_time' => '17:00',
                'end_time' => '21:00',
            ],
            [
                'day_of_week' => 2,
                'start_time' => '09:00',
                'end_time' => '18:00',
            ],
        ];

        $response = $this->withToken($this->token)->putJson('/api/doctor-availabilities/sync', [
            'doctor_id' => $this->doctor->id,
            'availabilities' => $availabilities,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Verify in database
        $this->assertDatabaseCount('doctor_availabilities', 3);
        $this->assertDatabaseHas('doctor_availabilities', [
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '13:00',
        ]);
        $this->assertDatabaseHas('doctor_availabilities', [
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 1,
            'start_time' => '17:00',
            'end_time' => '21:00',
        ]);
        $this->assertDatabaseHas('doctor_availabilities', [
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 2,
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]);
    }

    public function test_sync_clears_previous_availabilities(): void
    {
        // Create initial availability slot manually
        DoctorAvailability::create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 3,
            'start_time' => '10:00',
            'end_time' => '15:00',
        ]);

        $this->assertDatabaseCount('doctor_availabilities', 1);

        // Sync with a new empty array
        $response = $this->withToken($this->token)->putJson('/api/doctor-availabilities/sync', [
            'doctor_id' => $this->doctor->id,
            'availabilities' => [],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseCount('doctor_availabilities', 0);
    }
}
