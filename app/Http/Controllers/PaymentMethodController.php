<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use App\Http\Resources\PaymentMethodResource;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return PaymentMethodResource::collection(PaymentMethod::all());
    }
}
