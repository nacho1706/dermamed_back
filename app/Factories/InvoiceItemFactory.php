<?php

namespace App\Factories;

use App\Models\InvoiceItem;

class InvoiceItemFactory
{
    public static function fromRequest($request, ?InvoiceItem $item = null): InvoiceItem
    {
        $item = $item ?? new InvoiceItem;
        $item->invoice_id = isset($request['invoice_id']) ? $request['invoice_id'] : $item->invoice_id;
        $item->product_id = isset($request['product_id']) ? $request['product_id'] : $item->product_id;
        $item->service_id = isset($request['service_id']) ? $request['service_id'] : $item->service_id;
        $item->description = isset($request['description']) ? $request['description'] : $item->description;
        $item->quantity = isset($request['quantity']) ? $request['quantity'] : $item->quantity;
        $item->unit_price = isset($request['unit_price']) ? $request['unit_price'] : $item->unit_price;
        $item->subtotal = isset($request['subtotal']) ? $request['subtotal'] : $item->subtotal;

        return $item;
    }
}
