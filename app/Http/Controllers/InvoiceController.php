<?php

namespace App\Http\Controllers;

use App\Factories\InvoiceFactory;
use App\Http\Requests\Invoice\IndexInvoicesRequest;
use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Http\Requests\Invoice\UpdateInvoiceRequest;
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

        return response()->json([
            'data' => $paginador->items(),
            'current_page' => $paginador->currentPage(),
            'total_pages' => $paginador->lastPage(),
            'total_registros' => $paginador->total(),
        ]);
    }

    public function store(StoreInvoiceRequest $request)
    {
        $validated = $request->validated();

        $invoice = InvoiceFactory::fromRequest($validated);
        $invoice->save();

        return response()->json([
            'success' => true,
            'message' => 'Invoice created successfully',
            'invoice' => $invoice->load(['patient', 'voucherType']),
        ], 201);
    }

    public function show($id)
    {
        $invoice = Invoice::with(['patient', 'voucherType', 'items', 'payments.paymentMethod'])->find($id);

        if (! $invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'invoice' => $invoice,
        ]);
    }

    public function update(UpdateInvoiceRequest $request, $id)
    {
        $invoice = Invoice::find($id);

        if (! $invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found',
            ], 404);
        }

        $validated = $request->validated();

        $invoice = InvoiceFactory::fromRequest($validated, $invoice);
        $invoice->save();

        return response()->json([
            'success' => true,
            'message' => 'Invoice updated successfully',
            'invoice' => $invoice->load(['patient', 'voucherType']),
        ]);
    }

    public function destroy($id)
    {
        $invoice = Invoice::find($id);

        if (! $invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found',
            ], 404);
        }

        $invoice->delete();

        return response()->json([
            'success' => true,
            'message' => 'Invoice deleted successfully',
        ]);
    }
}
