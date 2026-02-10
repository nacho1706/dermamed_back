<?php

namespace App\Http\Controllers;

use App\Factories\InvoiceItemFactory;
use App\Http\Requests\InvoiceItem\StoreInvoiceItemRequest;
use App\Http\Requests\InvoiceItem\UpdateInvoiceItemRequest;
use App\Models\Invoice;
use App\Models\InvoiceItem;

class InvoiceItemController extends Controller
{
    public function store(StoreInvoiceItemRequest $request, $invoiceId)
    {
        $invoice = Invoice::find($invoiceId);

        if (! $invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found',
            ], 404);
        }

        $validated = $request->validated();
        $validated['invoice_id'] = $invoice->id;

        $item = InvoiceItemFactory::fromRequest($validated);
        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Invoice item created successfully',
            'invoice_item' => $item->load(['product', 'service']),
        ], 201);
    }

    public function show($invoiceId, $id)
    {
        $item = InvoiceItem::with(['invoice', 'product', 'service'])
            ->where('invoice_id', $invoiceId)
            ->find($id);

        if (! $item) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice item not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'invoice_item' => $item,
        ]);
    }

    public function update(UpdateInvoiceItemRequest $request, $invoiceId, $id)
    {
        $item = InvoiceItem::where('invoice_id', $invoiceId)->find($id);

        if (! $item) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice item not found',
            ], 404);
        }

        $validated = $request->validated();

        $item = InvoiceItemFactory::fromRequest($validated, $item);
        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Invoice item updated successfully',
            'invoice_item' => $item->load(['product', 'service']),
        ]);
    }

    public function destroy($invoiceId, $id)
    {
        $item = InvoiceItem::where('invoice_id', $invoiceId)->find($id);

        if (! $item) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice item not found',
            ], 404);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Invoice item deleted successfully',
        ]);
    }
}
