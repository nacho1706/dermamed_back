<?php

namespace Tests\Feature;

use App\Models\CashShift;
use App\Services\CashShiftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\AuthenticatesAsRole;
use Tests\TestCase;

class CashShiftRaceTest extends TestCase
{
    use RefreshDatabase, AuthenticatesAsRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_cannot_open_two_shifts_simultaneously(): void
    {
        $this->actingAs($this->receptionist());
        $service = new CashShiftService();

        $service->openShift(['opening_balance' => 0]);

        $this->expectException(ValidationException::class);
        $service->openShift(['opening_balance' => 0]);
    }

    public function test_partial_unique_index_enforces_single_open_shift_at_db_level(): void
    {
        // The Postgres / SQLite partial unique index is the last line of
        // defence after the application-level lockForUpdate. Even bypassing
        // the service via Eloquent::create() must not allow two open shifts.
        CashShift::query()->create([
            'opening_time' => now(),
            'initial_balance' => 0,
            'user_id_opened' => $this->receptionist()->id,
            'status' => 'open',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        CashShift::query()->create([
            'opening_time' => now(),
            'initial_balance' => 0,
            'user_id_opened' => $this->receptionist()->id,
            'status' => 'open',
        ]);
    }

    public function test_closing_persists_system_balance_and_difference_snapshot(): void
    {
        $this->actingAs($this->receptionist());
        $service = new CashShiftService();

        $shift = $service->openShift(['opening_balance' => 1000]);
        $closed = $service->closeShift([
            'closing_balance' => 1000,
            'justification' => null,
        ]);

        $this->assertEquals('closed', $closed->status);
        $this->assertNotNull($closed->system_balance);
        $this->assertNotNull($closed->difference);
        $this->assertEquals(1000.0, (float) $closed->final_balance);
    }
}
