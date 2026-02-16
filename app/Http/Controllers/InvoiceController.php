<?php

namespace App\Http\Controllers;

use App\Factories\InvoiceFactory;
use App\Http\Requests\Invoice\IndexInvoicesRequest;
use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Http\Requests\Invoice\UpdateInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;

class InvoiceController extends Controller
{
    public function index(IndexInvoicesRequest $request)
    {
        $validated = $request->validated();
        $cantidad = $validated['cantidad'] ?? 10;
        $pagina = $validated['pagina'] ?? 1;

        $query = Invoice::query()->with(['patient', 'voucherType']);

        if (isset($validated['patient_id'])) {
            $query->where('patient_id', $validated['patient_id']);
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $paginador = $query->orderBy('date', 'desc')->paginate($cantidad, ['*'], 'page', $pagina);

        return InvoiceResource::collection($paginador);
    }

    public function store(StoreInvoiceRequest $request)
    {
        $validated = $request->validated();

        $invoice = InvoiceFactory::fromRequest($validated);
        $invoice->save();
        $invoice->load(['patient', 'voucherType']);

        return (new InvoiceResource($invoice))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['patient', 'voucherType', 'items.product', 'items.service', 'payments.paymentMethod']);

        return new InvoiceResource($invoice);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        $validated = $request->validated();

        $invoice = InvoiceFactory::fromRequest($validated, $invoice);
        $invoice->save();
        $invoice->load(['patient', 'voucherType']);

        return new InvoiceResource($invoice);
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();

        return response()->json([
            'message' => 'Invoice deleted successfully',
        ]);
    }
}
