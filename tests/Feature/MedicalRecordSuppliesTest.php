<?php

namespace Tests\Feature;

use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesAsRole;
use Tests\TestCase;

/**
 * MedicalRecordController::store consumes "supplies" used during the
 * consultation. The bug closed in Sprint 2: previously only the
 * StockMovement ledger was inserted, leaving Product.stock untouched
 * — silent divergence between the inventory column and the movements.
 */
class MedicalRecordSuppliesTest extends TestCase
{
    use RefreshDatabase, AuthenticatesAsRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_storing_a_record_with_supplies_decrements_product_stock(): void
    {
        $product = Product::factory()->create(['stock' => 20]);
        $patient = Patient::factory()->create();

        $payload = [
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor()->id,
            'date' => now()->toIso8601String(),
            'content' => 'Consulta con uso de insumo X',
            'supplies' => [
                ['product_id' => $product->id, 'quantity' => 3],
            ],
        ];

        $response = $this->withToken($this->tokenFor($this->doctor()))
            ->postJson('/api/medical-records', $payload);

        $response->assertStatus(201);
        $this->assertEquals(17, $product->fresh()->stock);
        // And a ledger row was created
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'out',
            'quantity' => 3,
        ]);
    }

    public function test_storing_a_record_with_duplicate_supply_lines_aggregates_quantity(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $patient = Patient::factory()->create();

        $payload = [
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor()->id,
            'date' => now()->toIso8601String(),
            'content' => 'Dos administraciones del mismo insumo',
            'supplies' => [
                ['product_id' => $product->id, 'quantity' => 4],
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ];

        $this->withToken($this->tokenFor($this->doctor()))
            ->postJson('/api/medical-records', $payload)
            ->assertStatus(201);

        $this->assertEquals(4, $product->fresh()->stock); // 10 - (4+2)
    }

    public function test_insufficient_stock_rejects_the_record_creation(): void
    {
        $product = Product::factory()->create(['stock' => 1]);
        $patient = Patient::factory()->create();

        $payload = [
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor()->id,
            'date' => now()->toIso8601String(),
            'content' => 'Intento de consumir más de lo disponible',
            'supplies' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
        ];

        $this->withToken($this->tokenFor($this->doctor()))
            ->postJson('/api/medical-records', $payload)
            ->assertStatus(422);

        // Stock untouched because the transaction rolled back.
        $this->assertEquals(1, $product->fresh()->stock);
        // And the record itself wasn't persisted.
        $this->assertEquals(0, MedicalRecord::query()->where('patient_id', $patient->id)->count());
    }
}
