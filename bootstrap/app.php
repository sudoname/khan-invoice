<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'api.rate.limit' => \App\Http\Middleware\ApiRateLimit::class,
            'subscription.limit' => \App\Http\Middleware\EnforceSubscriptionLimits::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'payment/webhook',
            'webhook/public-invoice/paystack',
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        // Log performance metrics every hour
        $schedule->command('performance:log')->hourly();

        // Send payment reminders daily at 9:00 AM
        $schedule->command('reminders:send-payment')->dailyAt('09:00');

        // Check for overdue invoices daily at 10:00 AM
        $schedule->command('reminders:check-overdue')->dailyAt('10:00');

        // Check for expired subscriptions daily at 1:00 AM
        $schedule->command('subscriptions:check-expired')->dailyAt('01:00');

        // Reset subscription usage counters on the 1st of each month at 12:00 AM
        $schedule->command('subscriptions:reset-usage')->monthlyOn(1, '00:00');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
