<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\CashShift;
use App\Models\Patient;
use App\Models\PaymentMethod;
use App\Models\Service;
use App\Models\User;
use App\Models\VoucherType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class InvoiceTest extends TestCase
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

        // Asegurar caja abierta
        CashShift::create([
            'opening_time' => now(),
            'user_id_opened' => $this->user->id,
            'status' => 'open',
        ]);
    }

    public function test_can_create_invoice_for_appointment_in_waiting_room(): void
    {
        $patient = Patient::factory()->create();
        $service = Service::first();
        $voucherType = VoucherType::first();
        $paymentMethod = PaymentMethod::first();

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'service_id' => $service->id,
            'doctor_id' => $this->user->id,
            'scheduled_start_at' => now()->addHour(),
            'scheduled_end_at' => now()->addHours(2),
            'status' => 'in_waiting_room',
        ]);

        $response = $this->withToken($this->token)->postJson('/api/invoices', [
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'voucher_type_id' => $voucherType->id,
            'date' => now()->toDateString(),
            'items' => [
                [
                    'service_id' => $service->id,
                    'quantity' => 1,
                    'unit_price' => 5000,
                    'description' => $service->name,
                ],
            ],
            'payments' => [
                [
                    'payment_method_id' => $paymentMethod->id,
                    'amount' => 5000,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('invoices', [
            'appointment_id' => $appointment->id,
            'status' => 'paid',
        ]);
    }
}
