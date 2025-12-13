<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ReportController;
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
    Route::post('/auth/token', [AuthController::class, 'createToken']);
    Route::post('/auth/revoke', [AuthController::class, 'revokeToken'])->middleware('auth:sanctum');
});

// Protected routes
Route::prefix('v1')->middleware(['auth:sanctum', 'api.rate.limit'])->group(function () {
    // Invoices
    Route::apiResource('invoices', InvoiceController::class);

    // Customers
    Route::apiResource('customers', CustomerController::class);

    // Payments
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::post('/payments', [PaymentController::class, 'store']);

    // Reports
    Route::get('/reports/sales', [ReportController::class, 'sales']);
    Route::get('/reports/aging', [ReportController::class, 'aging']);
    Route::get('/reports/profit-loss', [ReportController::class, 'profitLoss']);
});
