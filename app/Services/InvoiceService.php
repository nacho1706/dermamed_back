<?php

namespace App\Services;

use App\Models\CashShift;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    /**
     * Create a complete sale: Invoice + Items + Stock deduction + Payment.
     * All wrapped in a DB transaction.
     *
     * @throws ValidationException
     */
    public function createSale(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            // 1. Validate open CashShift
            $cashShift = CashShift::where('status', 'open')->first();

            if (! $cashShift) {
                throw ValidationException::withMessages([
                    'cash_shift' => ['No hay una caja abierta. Abrí una caja antes de registrar una venta.'],
                ]);
            }

            // 2. Calculate total from items
            $totalAmount = 0;
            $itemsData = $data['items'] ?? [];

            // Pre-validate stock for products
            foreach ($itemsData as $index => $item) {
                if (! empty($item['product_id'])) {
                    $product = Product::where('id', $item['product_id'])->lockForUpdate()->first();

                    if (! $product) {
                        throw ValidationException::withMessages([
                            "items.{$index}.product_id" => ["El producto con ID {$item['product_id']} no existe."],
                        ]);
                    }

                    $quantity = $item['quantity'] ?? 1;

                    if ($product->stock < $quantity) {
                        throw ValidationException::withMessages([
                            "items.{$index}.quantity" => [
                                "Stock insuficiente para '{$product->name}'. Disponible: {$product->stock}, solicitado: {$quantity}.",
                            ],
                        ]);
                    }
                }
            }

            // 3. Create Invoice
            $invoice = Invoice::create([
                'patient_id' => $data['patient_id'],
                'voucher_type_id' => $data['voucher_type_id'] ?? null,
                'appointment_id' => $data['appointment_id'] ?? null,
                'date' => $data['date'] ?? now(),
                'total_amount' => 0, // Will be updated after items
                'status' => 'pending',
                'cae' => $data['cae'] ?? null,
            ]);

            // 4. Process Items
            foreach ($itemsData as $item) {
                $unitPrice = 0;
                $quantity = $item['quantity'] ?? 1;
                $description = $item['description'] ?? null;

                if (! empty($item['product_id'])) {
                    // ── Product item ─────────────────────────────────────────
                    $product = Product::where('id', $item['product_id'])->lockForUpdate()->first();
                    $unitPrice = $item['unit_price'] ?? $product->price;
                    $description = $description ?? $product->name;

                    // Deduct stock
                    $product->stock -= $quantity;
                    $product->save();

                    // Create stock movement
                    StockMovement::create([
                        'product_id' => $product->id,
                        'user_id' => auth()->id(),
                        'type' => 'out',
                        'quantity' => $quantity,
                        'reason' => 'patient_sale',
                    ]);
                } elseif (! empty($item['service_id'])) {
                    // ── Service item ─────────────────────────────────────────
                    $service = \App\Models\Service::findOrFail($item['service_id']);
                    $unitPrice = $item['unit_price'] ?? $service->price;
                    $description = $description ?? $service->name;
                }

                $subtotal = bcmul($unitPrice, $quantity, 2);
                $totalAmount = bcadd($totalAmount, $subtotal, 2);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'] ?? null,
                    'service_id' => $item['service_id'] ?? null,
                    'executor_doctor_id' => $item['executor_doctor_id'] ?? null,
                    'description' => $description,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);
            }

            // 5. Update invoice total
            $invoice->update([
                'total_amount' => $totalAmount,
                'status' => 'paid',
            ]);

            // 6. Register Payment
            if (! empty($data['payment'])) {
                $paymentData = $data['payment'];

                InvoicePayment::create([
                    'invoice_id' => $invoice->id,
                    'payment_method_id' => $paymentData['payment_method_id'],
                    'amount' => $paymentData['amount'] ?? $totalAmount,
                    'payment_date' => now(),
                    'cash_shift_id' => $cashShift->id,
                ]);
            }

            // 7. Load relationships and return
            $invoice->load([
                'patient',
                'voucherType',
                'appointment',
                'items.product',
                'items.service',
                'items.executorDoctor',
                'payments.paymentMethod',
                'payments.cashShift',
            ]);

            return $invoice;
        });
    }
}
