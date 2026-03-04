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

        $query = Invoice::query()->with(['patient', 'voucherType', 'appointment', 'items.product', 'items.service', 'items.executorDoctor', 'payments.paymentMethod']);

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
            $query->where('date', '<=', $validated['date_to'].' 23:59:59');
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
        $invoice = $this->invoiceService->updateSale($invoice, $request->validated());

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
            'data' => $history,
        ]);
    }
}
