<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
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
}
