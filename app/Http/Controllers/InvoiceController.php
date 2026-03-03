<?php

namespace App\Http\Controllers;

use App\Http\Requests\Invoice\IndexInvoicesRequest;
use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Http\Requests\Invoice\UpdateInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoiceService;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
    ) {}

    public function index(IndexInvoicesRequest $request)
    {
        $validated = $request->validated();
        $cantidad = $validated['cantidad'] ?? 10;
        $pagina = $validated['pagina'] ?? 1;

        $query = Invoice::query()->with(['patient', 'voucherType', 'appointment']);

        if (isset($validated['patient_id'])) {
            $query->where('patient_id', $validated['patient_id']);
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['cash_shift_id'])) {
            $query->whereHas('payments', function ($q) use ($validated) {
                $q->where('cash_shift_id', $validated['cash_shift_id']);
            });
        }

        if (isset($validated['date_from'])) {
            $query->where('date', '>=', $validated['date_from']);
        }

        if (isset($validated['date_to'])) {
            $query->where('date', '<=', $validated['date_to'] . ' 23:59:59');
        }

        $paginador = $query->orderBy('date', 'desc')->paginate($cantidad, ['*'], 'page', $pagina);

        return InvoiceResource::collection($paginador);
    }

    public function store(StoreInvoiceRequest $request)
    {
        $invoice = $this->invoiceService->createSale($request->validated());

        return (new InvoiceResource($invoice))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Invoice $invoice)
    {
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

        return new InvoiceResource($invoice);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        $validated = $request->validated();

        \Illuminate\Support\Facades\DB::transaction(function () use ($invoice, $validated) {
            $invoice->update([
                'patient_id' => $validated['patient_id'] ?? $invoice->patient_id,
                'voucher_type_id' => $validated['voucher_type_id'] ?? $invoice->voucher_type_id,
                'appointment_id' => $validated['appointment_id'] ?? $invoice->appointment_id,
                'date' => $validated['date'] ?? $invoice->date,
                'cae' => $validated['cae'] ?? $invoice->cae,
            ]);

            if (isset($validated['items'])) {
                $invoice->items()->delete();
                $totalAmount = 0;
                foreach ($validated['items'] as $item) {
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
            }

            \App\Models\InvoiceHistory::create([
                'invoice_id' => $invoice->id,
                'user_id' => auth()->id(),
                'action' => 'updated',
                'description' => 'Factura editada.',
            ]);
        });

        $invoice->load(['patient', 'voucherType', 'appointment', 'items', 'payments', 'items.product', 'items.service', 'items.executorDoctor', 'payments.paymentMethod', 'payments.cashShift']);

        return new InvoiceResource($invoice);
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();

        return response()->json([
            'success' => true,
            'message' => 'Invoice deleted successfully',
        ]);
    }

    public function history(Invoice $invoice)
    {
        $history = \App\Models\InvoiceHistory::where('invoice_id', $invoice->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $history
        ]);
    }
}
