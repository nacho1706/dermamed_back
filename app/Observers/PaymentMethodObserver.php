<?php

namespace App\Observers;

use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Cache;

class PaymentMethodObserver
{
    public function saved(PaymentMethod $paymentMethod): void
    {
        Cache::forget('payment_methods');
    }

    public function deleted(PaymentMethod $paymentMethod): void
    {
        Cache::forget('payment_methods');
    }
}
