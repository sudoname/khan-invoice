<?php

namespace App\Providers;

use App\Listeners\LogFailedEmail;
use App\Listeners\LogSentEmail;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Observers\PaymentObserver;
use App\Observers\InvoiceObserver;
use App\Observers\InvoiceItemObserver;
use App\Observers\UserObserver;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
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
        // Register User observer for automatic API access when email is verified
        User::observe(UserObserver::class);

        // Register Payment observer for automatic invoice status updates
        Payment::observe(PaymentObserver::class);

        // Register Invoice observer for automatic recalculation when tax/discount changes
        Invoice::observe(InvoiceObserver::class);

        // Register InvoiceItem observer for automatic invoice totals calculation
        InvoiceItem::observe(InvoiceItemObserver::class);

        // Register email logging listener
        Event::listen(MessageSent::class, LogSentEmail::class);
    }
}
