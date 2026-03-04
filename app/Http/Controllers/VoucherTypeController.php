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
        $types = \Illuminate\Support\Facades\Cache::rememberForever('voucher_types', function () {
            return VoucherType::all();
        });
        return VoucherTypeResource::collection($types);
    }
}
