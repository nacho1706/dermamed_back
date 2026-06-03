<?php

namespace Tests\Feature\Authorization;

use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesAsRole;
use Tests\TestCase;

/**
 * Doctor A must not be able to read, edit or delete medical records that
 * belong to Doctor B — this was the IDOR closed in Sprint 1 (Policies +
 * scoped index). Each test starts from a clean slate via RefreshDatabase
 * and seeds the three roles.
 */
class MedicalRecordIdorTest extends TestCase
{
    use RefreshDatabase, AuthenticatesAsRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_doctor_cannot_view_another_doctors_record(): void
    {
        $other = User::factory()->create();
        $other->roles()->attach(\App\Models\Role::where('name', 'doctor')->first());

        $record = MedicalRecord::factory()->create([
            'doctor_id' => $other->id,
            'patient_id' => Patient::factory()->create()->id,
        ]);

        $response = $this->withToken($this->tokenFor($this->doctor()))
            ->getJson("/api/medical-records/{$record->id}");

        $response->assertStatus(403);
    }

    public function test_doctor_can_view_own_record(): void
    {
        $doctor = $this->doctor();

        $record = MedicalRecord::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => Patient::factory()->create()->id,
        ]);

        $this->withToken($this->tokenFor($doctor))
            ->getJson("/api/medical-records/{$record->id}")
            ->assertStatus(200);
    }

    public function test_doctor_cannot_update_another_doctors_record(): void
    {
        $other = User::factory()->create();
        $other->roles()->attach(\App\Models\Role::where('name', 'doctor')->first());

        $record = MedicalRecord::factory()->create([
            'doctor_id' => $other->id,
            'patient_id' => Patient::factory()->create()->id,
            'content' => 'original',
        ]);

        $this->withToken($this->tokenFor($this->doctor()))
            ->putJson("/api/medical-records/{$record->id}", ['content' => 'hijacked'])
            ->assertStatus(403);

        $this->assertSame('original', $record->fresh()->content);
    }

    public function test_doctor_cannot_delete_another_doctors_record(): void
    {
        $other = User::factory()->create();
        $other->roles()->attach(\App\Models\Role::where('name', 'doctor')->first());

        $record = MedicalRecord::factory()->create([
            'doctor_id' => $other->id,
            'patient_id' => Patient::factory()->create()->id,
        ]);

        $this->withToken($this->tokenFor($this->doctor()))
            ->deleteJson("/api/medical-records/{$record->id}")
            ->assertStatus(403);

        $this->assertNotNull($record->fresh());
    }

    public function test_doctor_index_is_scoped_to_own_records_even_with_doctor_id_filter(): void
    {
        $other = User::factory()->create();
        $other->roles()->attach(\App\Models\Role::where('name', 'doctor')->first());

        $patient = Patient::factory()->create();
        MedicalRecord::factory()->create(['doctor_id' => $other->id, 'patient_id' => $patient->id]);
        $own = MedicalRecord::factory()->create(['doctor_id' => $this->doctor()->id, 'patient_id' => $patient->id]);

        // A "pure doctor" passing ?doctor_id=$other shouldn't see other's
        // records — the index force-filters by the authenticated doctor.
        $response = $this->withToken($this->tokenFor($this->doctor()))
            ->getJson("/api/medical-records?doctor_id={$other->id}");

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($own->id, $ids);
        $this->assertCount(1, $ids, 'Doctor must not see other doctors records');
    }

    public function test_receptionist_cannot_access_medical_records_at_all(): void
    {
        // Receptionist isn't in the role middleware list for medical-records
        // routes, so they get 403 from the RoleMiddleware before reaching
        // the policy.
        $this->withToken($this->tokenFor($this->receptionist()))
            ->getJson('/api/medical-records')
            ->assertStatus(403);
    }
}
