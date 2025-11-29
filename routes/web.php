<?php

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SocialAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

// Redirect /login to app panel login
Route::get('/login', function () {
    return redirect('/app/login');
});

Route::get('/policy/privacy', function () {
    return view('pages.privacy');
});

Route::get('/policy/terms', function () {
    return view('pages.terms');
});

Route::get('/about', function () {
    return view('pages.about');
});

Route::get('/contact', function () {
    return view('pages.contact');
});

Route::get('/auth/facebook/deletion', function () {
    return view('pages.facebook-deletion');
});

// Public invoice routes
Route::get('/inv/{publicId}', [InvoiceController::class, 'showPublic'])->name('invoice.public');
Route::get('/inv/{publicId}/download', [InvoiceController::class, 'downloadPdf'])->name('invoice.download');

// Payment routes
Route::post('/payment/{publicId}/initiate', [PaymentController::class, 'initiate'])->name('payment.initiate');
Route::get('/payment/callback', [PaymentController::class, 'callback'])->name('payment.callback');
Route::post('/payment/webhook', [PaymentController::class, 'webhook'])->name('payment.webhook');

// Social authentication routes (rate limited to prevent abuse)
Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
    ->middleware('throttle:10,1')
    ->name('social.redirect');
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->middleware('throttle:10,1')
    ->name('social.callback');
