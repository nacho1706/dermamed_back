<?php

namespace App\Http\Controllers;

use App\Factories\InvoiceItemFactory;
use App\Http\Requests\InvoiceItem\StoreInvoiceItemRequest;
use App\Http\Requests\InvoiceItem\UpdateInvoiceItemRequest;
use App\Http\Resources\InvoiceItemResource;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\StockMovementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceItemController extends Controller
{
    public function __construct(
        private readonly StockMovementService $stockService,
    ) {}

    public function store(StoreInvoiceItemRequest $request, Invoice $invoice)
    {
        $this->ensureEditable($invoice);

        return DB::transaction(function () use ($request, $invoice) {
            $invoice = Invoice::lockForUpdate()->findOrFail($invoice->id);
            $this->ensureEditable($invoice);

            $validated = $request->validated();
            $validated['invoice_id'] = $invoice->id;

            $item = InvoiceItemFactory::fromRequest($validated);
            $item->save();

            // If the new line is a product, discount stock and write ledger.
            if (! empty($item->product_id)) {
                $this->stockService->recordOut(
                    productId: (int) $item->product_id,
                    quantity: (int) $item->quantity,
                    reason: "Item agregado a factura #{$invoice->id}",
                );
            }

            $this->recalculateTotal($invoice);

            $item->load(['product', 'service']);

            return (new InvoiceItemResource($item))
                ->response()
                ->setStatusCode(201);
        });
    }

    public function show(Invoice $invoice, InvoiceItem $item)
    {
        $item->load(['product', 'service']);

        return new InvoiceItemResource($item);
    }

    public function update(UpdateInvoiceItemRequest $request, Invoice $invoice, InvoiceItem $item)
    {
        $this->ensureEditable($invoice);

        return DB::transaction(function () use ($request, $invoice, $item) {
            $invoice = Invoice::lockForUpdate()->findOrFail($invoice->id);
            $this->ensureEditable($invoice);

            $validated = $request->validated();
            $previousProductId = $item->product_id;
            $previousQuantity = (int) $item->quantity;

            $item = InvoiceItemFactory::fromRequest($validated, $item);
            $item->save();

            // Reconcile stock if the line touched a product. The simple,
            // robust path is: refund the previous product/qty and then take
            // out the new product/qty. Covers product swap as a side effect.
            if ($previousProductId) {
                $this->stockService->recordIn(
                    productId: (int) $previousProductId,
                    quantity: $previousQuantity,
                    reason: "Reversión por edición de item de factura #{$invoice->id}",
                );
            }
            if (! empty($item->product_id)) {
                $this->stockService->recordOut(
                    productId: (int) $item->product_id,
                    quantity: (int) $item->quantity,
                    reason: "Item editado en factura #{$invoice->id}",
                );
            }

            $this->recalculateTotal($invoice);

            $item->load(['product', 'service']);

            return new InvoiceItemResource($item);
        });
    }

    public function destroy(Invoice $invoice, InvoiceItem $item)
    {
        $this->ensureEditable($invoice);

        return DB::transaction(function () use ($invoice, $item) {
            $invoice = Invoice::lockForUpdate()->findOrFail($invoice->id);
            $this->ensureEditable($invoice);

            // Refund stock if this was a product line, before deleting the row.
            if (! empty($item->product_id)) {
                $this->stockService->recordIn(
                    productId: (int) $item->product_id,
                    quantity: (int) $item->quantity,
                    reason: "Item eliminado de factura #{$invoice->id}",
                );
            }

            $item->delete();

            $this->recalculateTotal($invoice);

            return response()->json([
                'message' => 'Invoice item deleted successfully',
            ]);
        });
    }

    private function ensureEditable(Invoice $invoice): void
    {
        if (in_array($invoice->status, ['paid', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'invoice' => ['No se pueden modificar items de una factura ya pagada o cancelada.'],
            ]);
        }
    }

    private function recalculateTotal(Invoice $invoice): void
    {
        $total = (string) $invoice->items()->sum('subtotal');
        $invoice->update(['total_amount' => $total]);
    }
}
