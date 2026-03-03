<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class StockMovementTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed the database to get roles and products
        $this->artisan('db:seed');

        $this->user = User::where('email', 'director@dermamed.com')->first();
        $this->token = JWTAuth::fromUser($this->user);
    }

    public function test_it_increases_stock_on_in_movement(): void
    {
        $product = Product::first();
        $initialStock = $product->stock;
        $quantity = 10;

        $response = $this->withToken($this->token)->postJson("/api/products/{$product->id}/movements", [
            'type' => 'in',
            'quantity' => $quantity,
            'reason' => 'supplier_purchase',
        ]);

        $response->assertStatus(201);

        $product->refresh();
        $this->assertEquals($initialStock + $quantity, $product->stock);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => $quantity,
            'reason' => 'supplier_purchase',
            'previous_stock' => $initialStock,
        ]);
    }

    public function test_it_decreases_stock_on_out_movement(): void
    {
        $product = Product::first();
        // Force stock to be sufficient
        $product->stock = 50;
        $product->save();

        $quantity = 5;

        $response = $this->withToken($this->token)->postJson("/api/products/{$product->id}/movements", [
            'type' => 'out',
            'quantity' => $quantity,
            'reason' => 'patient_sale',
        ]);

        $response->assertStatus(201);

        $product->refresh();
        $this->assertEquals(45, $product->stock);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'out',
            'previous_stock' => 50,
        ]);
    }

    public function test_it_prevents_negative_stock(): void
    {
        $product = Product::first();
        $product->stock = 5;
        $product->save();

        $response = $this->withToken($this->token)->postJson("/api/products/{$product->id}/movements", [
            'type' => 'out',
            'quantity' => 10,
            'reason' => 'internal_use',
        ]);

        $response->assertStatus(422);

        $product->refresh();
        $this->assertEquals(5, $product->stock);
    }

    public function test_adjustment_is_absolute_overwrite(): void
    {
        $product = Product::first();
        $product->stock = 30;
        $product->save();

        // Adjustment with quantity=15 means new stock IS 15 (not 30-15=15 or 30+15=45)
        $response = $this->withToken($this->token)->postJson("/api/products/{$product->id}/movements", [
            'type' => 'adjustment',
            'quantity' => 15,
            'reason' => 'inventory_correction',
        ]);

        $response->assertStatus(201);

        $product->refresh();
        $this->assertEquals(15, $product->stock);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'adjustment',
            'quantity' => 15,
            'previous_stock' => 30,
            'reason' => 'inventory_correction',
        ]);
    }

    public function test_adjustment_can_set_stock_to_any_value(): void
    {
        $product = Product::first();
        $product->stock = 5;
        $product->save();

        // Even setting to a value higher than current stock is allowed for adjustment
        $response = $this->withToken($this->token)->postJson("/api/products/{$product->id}/movements", [
            'type' => 'adjustment',
            'quantity' => 100,
            'reason' => 'inventory_correction',
        ]);

        $response->assertStatus(201);

        $product->refresh();
        $this->assertEquals(100, $product->stock);
    }

    public function test_ledger_previous_stock_is_stored(): void
    {
        $product = Product::first();
        $product->stock = 40;
        $product->save();

        $response = $this->withToken($this->token)->postJson("/api/products/{$product->id}/movements", [
            'type' => 'in',
            'quantity' => 10,
            'reason' => 'supplier_purchase',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.previous_stock', 40);
        $response->assertJsonPath('data.quantity', 10);
    }

    public function test_update_product_request_ignores_stock_field(): void
    {
        $product = Product::first();
        $product->stock = 10;
        $product->save();

        $response = $this->withToken($this->token)->putJson("/api/products/{$product->id}", [
            'stock' => 500, // Should be ignored
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200);

        $product->refresh();
        $this->assertEquals(10, $product->stock);
        $this->assertEquals('Updated Name', $product->name);
    }

    public function test_conditional_reason_in_type_rejects_wrong_reason(): void
    {
        $product = Product::first();
        $product->stock = 20;
        $product->save();

        // 'patient_sale' is only for 'out', not 'in'
        $response = $this->withToken($this->token)->postJson("/api/products/{$product->id}/movements", [
            'type' => 'in',
            'quantity' => 5,
            'reason' => 'patient_sale',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['reason']);
    }

    public function test_conditional_reason_out_type_rejects_wrong_reason(): void
    {
        $product = Product::first();
        $product->stock = 20;
        $product->save();

        // 'supplier_purchase' is only for 'in', not 'out'
        $response = $this->withToken($this->token)->postJson("/api/products/{$product->id}/movements", [
            'type' => 'out',
            'quantity' => 5,
            'reason' => 'supplier_purchase',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['reason']);
    }

    public function test_conditional_reason_adjustment_type_rejects_wrong_reason(): void
    {
        $product = Product::first();
        $product->stock = 20;
        $product->save();

        // 'supplier_purchase' is only for 'in', not adjustments
        $response = $this->withToken($this->token)->postJson("/api/products/{$product->id}/movements", [
            'type' => 'adjustment',
            'quantity' => 10,
            'reason' => 'supplier_purchase',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['reason']);
    }
}
