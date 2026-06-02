<?php

namespace App\Services;

use App\Models\CashShift;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function __construct(
        private readonly StockMovementService $stockService,
    ) {}

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

            $itemsData = $data['items'] ?? [];

            // Aggregate product quantities so duplicate lines of the same
            // product validate (and discount) against the combined total —
            // previously two lines of qty=3 of a product with stock=5 each
            // passed validation individually and ended up at stock=-1.
            $aggregatedQty = [];
            foreach ($itemsData as $item) {
                if (! empty($item['product_id'])) {
                    $pid = (int) $item['product_id'];
                    $aggregatedQty[$pid] = ($aggregatedQty[$pid] ?? 0) + (int) ($item['quantity'] ?? 1);
                }
            }

            // Pre-validate stock for products against the aggregated quantity
            foreach ($aggregatedQty as $productId => $totalQty) {
                $product = Product::where('id', $productId)->lockForUpdate()->first();

                if (! $product) {
                    throw ValidationException::withMessages([
                        'items.product_id' => ["El producto con ID {$productId} no existe."],
                    ]);
                }

                if ($product->stock < $totalQty) {
                    throw ValidationException::withMessages([
                        'items.quantity' => [
                            "Stock insuficiente para '{$product->name}'. Disponible: {$product->stock}, solicitado: {$totalQty}.",
                        ],
                    ]);
                }
            }

            // 2. Create Invoice
            $invoice = Invoice::create([
                'patient_id' => $data['patient_id'],
                'voucher_type_id' => $data['voucher_type_id'] ?? null,
                'appointment_id' => $data['appointment_id'] ?? null,
                'date' => $data['date'] ?? now(),
                'total_amount' => 0, // Will be updated after items
                'status' => 'pending',
                'cae' => $data['cae'] ?? null,
            ]);

            // 3. Process Items
            $totalAmount = 0;
            foreach ($itemsData as $item) {
                $unitPrice = 0;
                $quantity = $item['quantity'] ?? 1;
                $description = $item['description'] ?? null;

                if (! empty($item['product_id'])) {
                    // Resolve unit_price and name without taking another lock
                    $product = Product::find($item['product_id']);
                    $unitPrice = $item['unit_price'] ?? $product->price;
                    $description = $description ?? $product->name;
                    // Stock is discounted in bulk below (one call per product).
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

            // 4. Discount stock in bulk: one ledger entry per product with
            // the aggregated quantity, so the ledger reflects the sale and
            // Product.stock stays in sync.
            foreach ($aggregatedQty as $productId => $totalQty) {
                $this->stockService->recordOut(
                    productId: $productId,
                    quantity: $totalQty,
                    reason: "Venta de factura #{$invoice->id}",
                );
            }

            // 5. Register Payments and Calculate Status
            $totalPaid = 0;
            if (! empty($data['payments'])) {
                foreach ($data['payments'] as $paymentData) {
                    $amount = $paymentData['amount'];
                    $totalPaid = bcadd($totalPaid, $amount, 2);

                    InvoicePayment::create([
                        'invoice_id' => $invoice->id,
                        'payment_method_id' => $paymentData['payment_method_id'],
                        'amount' => $amount,
                        'payment_date' => now(),
                        'cash_shift_id' => $cashShift->id,
                    ]);
                }
            }

            // 6. Update invoice total and status
            $isPaid = bccomp($totalPaid, $totalAmount, 2) >= 0;

            $invoice->update([
                'total_amount' => $totalAmount,
                'status' => $isPaid ? 'paid' : 'pending',
            ]);

            $descriptionText = 'Factura creada.';
            if ($totalPaid > 0) {
                $descriptionText .= ' Pago inicial: $'.number_format($totalPaid, 2, ',', '.').'.';
            } else {
                $descriptionText .= ' '.($isPaid ? 'Monto pagado completamente.' : 'Pagos parciales / Pendiente.');
            }

            \App\Models\InvoiceHistory::create([
                'invoice_id' => $invoice->id,
                'user_id' => auth()->id(),
                'action' => 'created',
                'description' => $descriptionText,
            ]);

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

    /**
     * Update an existing sale: Invoice + Items.
     * All wrapped in a DB transaction.
     *
     * @throws ValidationException
     */
    public function updateSale(Invoice $invoice, array $validatedData): Invoice
    {
        // Editing a paid/cancelled invoice is forbidden: emit a credit note
        // (voiding the invoice) and create a new one instead.
        if (in_array($invoice->status, ['paid', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'invoice' => ['No se puede editar una factura ya pagada o cancelada. Anulala y emití una nueva.'],
            ]);
        }

        return DB::transaction(function () use ($invoice, $validatedData) {
            $oldTotal = $invoice->total_amount;

            $invoice->update([
                'patient_id' => $validatedData['patient_id'] ?? $invoice->patient_id,
                'voucher_type_id' => $validatedData['voucher_type_id'] ?? $invoice->voucher_type_id,
                'appointment_id' => $validatedData['appointment_id'] ?? $invoice->appointment_id,
                'date' => $validatedData['date'] ?? $invoice->date,
                'cae' => $validatedData['cae'] ?? $invoice->cae,
            ]);

            $totalAmount = $oldTotal;
            if (isset($validatedData['items'])) {
                // 1. Snapshot of the OLD product quantities, so we can return
                // each one to stock before discounting the new lines.
                $oldProductQty = [];
                foreach ($invoice->items()->whereNotNull('product_id')->get() as $oldItem) {
                    $pid = (int) $oldItem->product_id;
                    $oldProductQty[$pid] = ($oldProductQty[$pid] ?? 0) + (int) $oldItem->quantity;
                }

                // 2. Aggregate NEW product quantities.
                $newProductQty = [];
                foreach ($validatedData['items'] as $item) {
                    if (! empty($item['product_id'])) {
                        $pid = (int) $item['product_id'];
                        $newProductQty[$pid] = ($newProductQty[$pid] ?? 0) + (int) ($item['quantity'] ?? 1);
                    }
                }

                // 3. Return all old stock (compensatory IN movements).
                foreach ($oldProductQty as $productId => $qty) {
                    $this->stockService->recordIn(
                        productId: $productId,
                        quantity: $qty,
                        reason: "Reversión por edición de factura #{$invoice->id}",
                    );
                }

                // 4. Delete old items and rebuild.
                $invoice->items()->delete();
                $totalAmount = 0;
                foreach ($validatedData['items'] as $item) {
                    $unitPrice = $item['unit_price'] ?? 0;
                    $quantity = $item['quantity'] ?? 1;
                    $subtotal = bcmul($unitPrice, $quantity, 2);
                    $totalAmount = bcadd($totalAmount, $subtotal, 2);

                    \App\Models\InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $item['product_id'] ?? null,
                        'service_id' => $item['service_id'] ?? null,
                        'executor_doctor_id' => $item['executor_doctor_id'] ?? null,
                        'description' => $item['description'] ?? '',
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'subtotal' => $subtotal,
                    ]);
                }
                $invoice->update(['total_amount' => $totalAmount]);

                // 5. Discount stock for the NEW aggregated quantities.
                foreach ($newProductQty as $productId => $qty) {
                    $this->stockService->recordOut(
                        productId: $productId,
                        quantity: $qty,
                        reason: "Venta por edición de factura #{$invoice->id}",
                    );
                }
            }

            $diffText = 'Factura editada.';
            if (isset($totalAmount) && $oldTotal != $totalAmount) {
                $diffText .= ' El total cambió de $'.number_format($oldTotal, 2, ',', '.').' a $'.number_format($totalAmount, 2, ',', '.').'.';
            }
            if (isset($validatedData['items'])) {
                $diffText .= ' Ítems modificados.';
            }

            \App\Models\InvoiceHistory::create([
                'invoice_id' => $invoice->id,
                'user_id' => auth()->id(),
                'action' => 'updated',
                'description' => $diffText,
            ]);

            $invoice->load([
                'patient',
                'voucherType',
                'appointment',
                'items',
                'payments',
                'items.product',
                'items.service',
                'items.executorDoctor',
                'payments.paymentMethod',
                'payments.cashShift',
            ]);

            return $invoice;
        });
    }

    /**
     * Cancel/void a sale: returns product stock, marks invoice as cancelled
     * (soft-delete via the model SoftDeletes if present, else just status),
     * and writes a history entry. Caller must ensure proper authorization.
     */
    public function cancelSale(Invoice $invoice, ?string $reason = null): void
    {
        if ($invoice->status === 'cancelled') {
            return;
        }

        DB::transaction(function () use ($invoice, $reason) {
            // Return all sold products to stock.
            $productQty = [];
            foreach ($invoice->items()->whereNotNull('product_id')->get() as $item) {
                $pid = (int) $item->product_id;
                $productQty[$pid] = ($productQty[$pid] ?? 0) + (int) $item->quantity;
            }

            foreach ($productQty as $productId => $qty) {
                $this->stockService->recordIn(
                    productId: $productId,
                    quantity: $qty,
                    reason: "Anulación de factura #{$invoice->id}",
                );
            }

            $invoice->update(['status' => 'cancelled']);

            \App\Models\InvoiceHistory::create([
                'invoice_id' => $invoice->id,
                'user_id' => auth()->id(),
                'action' => 'cancelled',
                'description' => 'Factura anulada.'.($reason ? ' Motivo: '.$reason : ''),
            ]);

            // Soft-delete the record (Invoice uses SoftDeletes if the trait is
            // enabled on the model; if not, the cancelled status is enough).
            $invoice->delete();
        });
    }
}
