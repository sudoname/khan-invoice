<?php

use App\Http\Controllers\Api\V1\AISuggestionController;
use App\Http\Controllers\Api\V1\AnalyticsEventController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BusinessProfileController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\MarketingController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\SocialAuthController;
use App\Http\Controllers\Api\PaymentController as NewPaymentController;
use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\WhatsApp\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Webhook routes (no auth middleware)
Route::post('/webhooks/paystack', [PaystackWebhookController::class, 'handle']);

// Payment Orchestration Webhooks (with signature verification)
Route::prefix('webhooks/payment')->group(function () {
    Route::post('/paystack', [PaymentWebhookController::class, 'handlePaystackWebhook'])
        ->middleware('verify.payment.webhook:paystack');

    Route::post('/flutterwave', [PaymentWebhookController::class, 'handleFlutterwaveWebhook'])
        ->middleware('verify.payment.webhook:flutterwave');
});

// WhatsApp Webhooks (no auth - verified via signature and verify token)
Route::prefix('webhooks/whatsapp')->group(function () {
    // GET endpoint for webhook verification
    Route::get('/', [WhatsAppWebhookController::class, 'verify'])
        ->name('whatsapp.webhook.verify');

    // POST endpoint for receiving messages
    Route::post('/', [WhatsAppWebhookController::class, 'receive'])
        ->name('whatsapp.webhook.receive');
});

// Public routes
Route::prefix('v1')->group(function () {
    // Authentication
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/token', [AuthController::class, 'createToken']);

    // Social Authentication (mobile)
    Route::post('/auth/google', [SocialAuthController::class, 'googleLogin']);
    Route::post('/auth/facebook', [SocialAuthController::class, 'facebookLogin']);

    // Contact form (public - no auth required)
    Route::post('/contact', [ContactController::class, 'store']);

    // Analytics events (public - rate limited to 60 req/min per IP)
    Route::post('/analytics/events', [AnalyticsEventController::class, 'store'])
        ->middleware('throttle:60,1');

    // Marketing demo (public - rate limited to 3 req/hour per IP)
    Route::post('/marketing/generate-demo', [MarketingController::class, 'generateDemo']);
    Route::get('/marketing/designs/{design}/status', [MarketingController::class, 'checkDesignStatus']);
    Route::get('/marketing/templates', [MarketingController::class, 'templates']);

    // Protected auth routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/user', [AuthController::class, 'user']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/revoke', [AuthController::class, 'revokeToken']);
    });
});

// Protected routes
Route::prefix('v1')->middleware(['auth:sanctum', 'api.rate.limit'])->group(function () {
    // Invoices
    Route::apiResource('invoices', InvoiceController::class);

    // Customers
    Route::apiResource('customers', CustomerController::class);

    // Business Profiles
    Route::apiResource('business-profiles', BusinessProfileController::class);

    // Payments
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::get('/payments/{id}', [PaymentController::class, 'show']);

    // Reports
    Route::get('/reports/sales', [ReportController::class, 'sales']);
    Route::get('/reports/aging', [ReportController::class, 'aging']);
    Route::get('/reports/profit-loss', [ReportController::class, 'profitLoss']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Subscription & Plans
    Route::get('/subscription', [SubscriptionController::class, 'current']);
    Route::get('/plans', [SubscriptionController::class, 'plans']);

    // Payment Orchestration (Feature Flagged)
    Route::prefix('payments')->group(function () {
        Route::post('/invoices/{invoiceUuid}/initialize', [NewPaymentController::class, 'initializePayment']);
        Route::get('/verify/{reference}', [NewPaymentController::class, 'verifyPayment']);
        Route::get('/invoices/{invoiceUuid}/attempts', [NewPaymentController::class, 'getPaymentAttempts']);
    });

    // AI Features (with custom rate limiting)
    Route::prefix('ai')->group(function () {
        // Smart Suggestions
        Route::get('/suggest/customers', [AISuggestionController::class, 'suggestCustomers']);
        Route::get('/suggest/items', [AISuggestionController::class, 'suggestItems']);
        Route::get('/suggest/due-date', [AISuggestionController::class, 'suggestDueDate']);

        // Payment Reminders
        Route::get('/reminders/plan/{invoice}', [AISuggestionController::class, 'planReminders']);
        Route::post('/reminders/{invoice}', [AISuggestionController::class, 'createReminderPlan']);

        // Insights & Analytics
        Route::get('/insights', [AISuggestionController::class, 'getInsights']);

        // Statistics
        Route::get('/stats', [AISuggestionController::class, 'getStatistics']);
    });

    // Marketing AI Generator (with custom rate limiting)
    Route::prefix('marketing')->group(function () {
        // Design Generation
        Route::post('/generate', [MarketingController::class, 'generate']);
        Route::post('/from-invoice/{invoice}', [MarketingController::class, 'fromInvoice']);

        // User Designs
        Route::get('/designs', [MarketingController::class, 'designs']);
        Route::get('/designs/{design}', [MarketingController::class, 'show']);
        Route::delete('/designs/{design}', [MarketingController::class, 'destroy']);
        Route::post('/designs/{design}/download', [MarketingController::class, 'trackDownload']);

        // Brand Kits
        Route::get('/brand-kits', [MarketingController::class, 'brandKits']);
        Route::post('/brand-kits', [MarketingController::class, 'storeBrandKit']);
        Route::put('/brand-kits/{brandKit}', [MarketingController::class, 'updateBrandKit']);
        Route::delete('/brand-kits/{brandKit}', [MarketingController::class, 'destroyBrandKit']);

        // Statistics
        Route::get('/stats', [MarketingController::class, 'stats']);
    });
});

// WhatsApp Routes
require __DIR__ . '/whatsapp.php';
