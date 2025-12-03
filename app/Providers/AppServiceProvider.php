<?php

namespace App\Providers;

use App\Models\Payment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Observers\PaymentObserver;
use App\Observers\InvoiceObserver;
use App\Observers\InvoiceItemObserver;
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
        // Register Payment observer for automatic invoice status updates
        Payment::observe(PaymentObserver::class);

        // Register Invoice observer for automatic recalculation when tax/discount changes
        Invoice::observe(InvoiceObserver::class);

        // Register InvoiceItem observer for automatic invoice totals calculation
        InvoiceItem::observe(InvoiceItemObserver::class);
    }
}
