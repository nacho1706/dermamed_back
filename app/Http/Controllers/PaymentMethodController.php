<?php

namespace App\Http\Controllers;

use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;

class PaymentMethodController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $methods = \Illuminate\Support\Facades\Cache::rememberForever('payment_methods', function () {
            return PaymentMethod::all();
        });
        return PaymentMethodResource::collection($methods);
    }
}
