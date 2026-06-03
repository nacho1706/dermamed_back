<?php

namespace Tests\Unit\Services;

use App\Models\CashShift;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use App\Models\VoucherType;
use App\Services\InvoiceService;
use App\Services\StockMovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceService $service;
    private User $actor;
    private Patient $patient;
    private VoucherType $voucherType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');

        $this->service = new InvoiceService(new StockMovementService());
        $this->actor = User::where('email', 'recepcion@dermamed.com')->firstOrFail();
        $this->patient = Patient::factory()->create();
        $this->voucherType = VoucherType::query()->first() ?? VoucherType::factory()->create();
        $this->actingAs($this->actor); // so auth()->id() inside the service resolves
    }

    private function openShift(): CashShift
    {
        return CashShift::factory()->create(['user_id_opened' => $this->actor->id]);
    }

    public function test_create_sale_creates_invoice_items_payment_and_decrements_stock(): void
    {
        $this->openShift();
        $cash = PaymentMethod::query()->first()->id ?? PaymentMethod::factory()->create()->id;
        $product = Product::factory()->create(['stock' => 10, 'price' => 1000]);

        $invoice = $this->service->createSale([
            'patient_id' => $this->patient->id,
            'voucher_type_id' => $this->voucherType->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 1000],
            ],
            'payments' => [
                ['payment_method_id' => $cash, 'amount' => 3000],
            ],
        ]);

        $this->assertEquals(7, $product->fresh()->stock);
        $this->assertEquals('paid', $invoice->status);
        $this->assertCount(1, $invoice->items);
        $this->assertCount(1, $invoice->payments);
    }

    public function test_create_sale_aggregates_duplicate_product_lines_before_validating_stock(): void
    {
        // The bug closed in Sprint 2: stock=5, two lines of qty=3 of the
        // same product used to pass per-line validation and left stock=-1.
        $this->openShift();
        $product = Product::factory()->create(['stock' => 5, 'price' => 1000]);

        $this->expectException(ValidationException::class);

        $this->service->createSale([
            'patient_id' => $this->patient->id,
            'voucher_type_id' => $this->voucherType->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 1000],
                ['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 1000],
            ],
            'payments' => [],
        ]);

        $this->assertEquals(5, $product->fresh()->stock);
    }

    public function test_create_sale_fails_without_open_cash_shift(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        // No CashShift opened.

        $this->expectException(ValidationException::class);

        $this->service->createSale([
            'patient_id' => $this->patient->id,
            'voucher_type_id' => $this->voucherType->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100]],
            'payments' => [],
        ]);
    }

    public function test_create_sale_marks_invoice_as_pending_when_no_payments(): void
    {
        $this->openShift();
        $product = Product::factory()->create(['stock' => 10, 'price' => 1000]);

        $invoice = $this->service->createSale([
            'patient_id' => $this->patient->id,
            'voucher_type_id' => $this->voucherType->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 1000]],
            'payments' => [],
        ]);

        $this->assertEquals('pending', $invoice->status);
        $this->assertEquals(0, $invoice->payments->count());
    }

    public function test_update_sale_returns_old_stock_and_discounts_new_stock(): void
    {
        $this->openShift();
        $oldProduct = Product::factory()->create(['stock' => 10, 'price' => 500]);
        $newProduct = Product::factory()->create(['stock' => 10, 'price' => 500]);

        $invoice = $this->service->createSale([
            'patient_id' => $this->patient->id,
            'voucher_type_id' => $this->voucherType->id,
            'items' => [['product_id' => $oldProduct->id, 'quantity' => 4, 'unit_price' => 500]],
            'payments' => [],
        ]);
        $this->assertEquals(6, $oldProduct->fresh()->stock);

        // Replace the line entirely with another product.
        $this->service->updateSale($invoice, [
            'items' => [['product_id' => $newProduct->id, 'quantity' => 2, 'unit_price' => 500]],
        ]);

        // Old product must have its 4 units returned, new one must drop by 2.
        $this->assertEquals(10, $oldProduct->fresh()->stock, 'old stock not restored');
        $this->assertEquals(8, $newProduct->fresh()->stock, 'new stock not discounted');
    }

    public function test_update_sale_throws_when_invoice_is_paid(): void
    {
        $this->openShift();
        $cash = PaymentMethod::query()->first()->id;
        $product = Product::factory()->create(['stock' => 10, 'price' => 1000]);

        $invoice = $this->service->createSale([
            'patient_id' => $this->patient->id,
            'voucher_type_id' => $this->voucherType->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 1000]],
            'payments' => [['payment_method_id' => $cash, 'amount' => 1000]],
        ]);
        $this->assertEquals('paid', $invoice->status);

        $this->expectException(ValidationException::class);
        $this->service->updateSale($invoice, [
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 1000]],
        ]);
    }

    public function test_cancel_sale_returns_stock_marks_status_and_soft_deletes(): void
    {
        $this->openShift();
        $product = Product::factory()->create(['stock' => 10, 'price' => 1000]);

        $invoice = $this->service->createSale([
            'patient_id' => $this->patient->id,
            'voucher_type_id' => $this->voucherType->id,
            'items' => [['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 1000]],
            'payments' => [],
        ]);
        $this->assertEquals(7, $product->fresh()->stock);

        $this->service->cancelSale($invoice, 'Test cancellation');

        $this->assertEquals(10, $product->fresh()->stock, 'stock not restored on cancel');
        $reloaded = Invoice::withTrashed()->find($invoice->id);
        $this->assertEquals('cancelled', $reloaded->status);
        $this->assertNotNull($reloaded->deleted_at);
    }
}
