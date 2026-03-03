<?php

namespace App\Http\Controllers;

use App\Http\Resources\VoucherTypeResource;
use App\Models\VoucherType;

class VoucherTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return VoucherTypeResource::collection(VoucherType::all());
    }
}
