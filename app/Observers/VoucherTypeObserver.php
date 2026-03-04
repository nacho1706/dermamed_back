<?php

namespace App\Observers;

use App\Models\VoucherType;
use Illuminate\Support\Facades\Cache;

class VoucherTypeObserver
{
    public function saved(VoucherType $voucherType): void
    {
        Cache::forget('voucher_types');
    }

    public function deleted(VoucherType $voucherType): void
    {
        Cache::forget('voucher_types');
    }
}
