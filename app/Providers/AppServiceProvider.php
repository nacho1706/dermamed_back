<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \App\Models\PaymentMethod::observe(\App\Observers\PaymentMethodObserver::class);
        \App\Models\Role::observe(\App\Observers\RoleObserver::class);
        \App\Models\VoucherType::observe(\App\Observers\VoucherTypeObserver::class);
    }
}
