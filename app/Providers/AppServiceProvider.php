<?php

namespace App\Providers;

use App\Listeners\LogFailedEmail;
use App\Listeners\LogSentEmail;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PublicInvoice;
use App\Models\User;
use App\Models\Payment\Payout;
use App\Observers\PaymentObserver;
use App\Observers\InvoiceObserver;
use App\Observers\InvoiceItemObserver;
use App\Observers\PublicInvoiceObserver;
use App\Observers\UserObserver;
use App\Observers\PayoutObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
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

        // Register PublicInvoice observer for document hash updates
        PublicInvoice::observe(PublicInvoiceObserver::class);

        // Register InvoiceItem observer for automatic invoice totals calculation
        InvoiceItem::observe(InvoiceItemObserver::class);

        // Register Payout observer for accounting integrity enforcement
        Payout::observe(PayoutObserver::class);

        // Register email logging listener
        Event::listen(MessageSent::class, LogSentEmail::class);

        // Register AI rate limiters
        $this->registerAIRateLimiters();
    }

    /**
     * Register custom rate limiters for AI endpoints
     */
    protected function registerAIRateLimiters(): void
    {
        $config = config('kinvoice.ai.rate_limits');

        // AI Suggestions rate limiter
        RateLimiter::for('ai_suggestions', function (Request $request) use ($config) {
            return Limit::perMinute($config['suggestions']['max_attempts'])
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'error' => 'Too many suggestion requests. Please try again later.',
                    ], 429);
                });
        });

        // AI Reminders rate limiter
        RateLimiter::for('ai_reminders', function (Request $request) use ($config) {
            return Limit::perMinute($config['reminders']['max_attempts'])
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'error' => 'Too many reminder requests. Please try again later.',
                    ], 429);
                });
        });

        // AI Insights rate limiter
        RateLimiter::for('ai_insights', function (Request $request) use ($config) {
            return Limit::perMinute($config['insights']['max_attempts'])
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'error' => 'Too many insight requests. Please try again later.',
                    ], 429);
                });
        });
    }
}
