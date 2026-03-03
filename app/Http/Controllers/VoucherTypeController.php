<?php

namespace App\Http\Controllers;

use App\Models\VoucherType;
use App\Http\Resources\VoucherTypeResource;
use Illuminate\Http\Request;

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
