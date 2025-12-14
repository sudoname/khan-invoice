<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\PaystackWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Webhook routes (no auth middleware)
Route::post('/webhooks/paystack', [PaystackWebhookController::class, 'handle']);

// Public routes
Route::prefix('v1')->group(function () {
    // Authentication
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/token', [AuthController::class, 'createToken']);

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
});
