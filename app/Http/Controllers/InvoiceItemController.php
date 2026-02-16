<?php

namespace App\Http\Controllers;

use App\Factories\InvoiceItemFactory;
use App\Http\Requests\InvoiceItem\StoreInvoiceItemRequest;
use App\Http\Requests\InvoiceItem\UpdateInvoiceItemRequest;
use App\Http\Resources\InvoiceItemResource;
use App\Models\Invoice;
use App\Models\InvoiceItem;

class InvoiceItemController extends Controller
{
    public function store(StoreInvoiceItemRequest $request, Invoice $invoice)
    {
        $validated = $request->validated();
        $validated['invoice_id'] = $invoice->id;

        $item = InvoiceItemFactory::fromRequest($validated);
        $item->save();
        $item->load(['product', 'service']);

        return (new InvoiceItemResource($item))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Invoice $invoice, InvoiceItem $item)
    {
        $item->load(['product', 'service']);

        return new InvoiceItemResource($item);
    }

    public function update(UpdateInvoiceItemRequest $request, Invoice $invoice, InvoiceItem $item)
    {
        $validated = $request->validated();

        $item = InvoiceItemFactory::fromRequest($validated, $item);
        $item->save();
        $item->load(['product', 'service']);

        return new InvoiceItemResource($item);
    }

    public function destroy(Invoice $invoice, InvoiceItem $item)
    {
        $item->delete();

        return response()->json([
            'message' => 'Invoice item deleted successfully',
        ]);
    }
}
