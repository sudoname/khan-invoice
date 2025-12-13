<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\PublicInvoiceController;
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

Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

Route::get('/faq', function () {
    return view('pages.faq');
});

Route::get('/auth/facebook/deletion', function () {
    return view('pages.facebook-deletion');
});

// Pricing and Subscription routes
Route::get('/pricing', [PricingController::class, 'index'])->name('pricing');
Route::post('/pricing/select-plan/{planSlug}', [PricingController::class, 'selectPlan'])->name('pricing.select');

// Checkout routes (requires authentication)
Route::middleware('auth')->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
    Route::post('/checkout/initialize', [CheckoutController::class, 'initializePayment'])->name('checkout.initialize');
    Route::get('/checkout/verify', [CheckoutController::class, 'verifyPayment'])->name('checkout.verify');
});

// Public Invoice Generator routes
Route::get('/invoice-generator', [PublicInvoiceController::class, 'create'])->name('public-invoice.create');
Route::post('/invoice-generator/preview', [PublicInvoiceController::class, 'preview'])->name('public-invoice.preview');
Route::get('/invoice/{publicId}', [PublicInvoiceController::class, 'show'])->name('public-invoice.show');
Route::get('/invoice/{publicId}/download', [PublicInvoiceController::class, 'download'])->name('public-invoice.download');
Route::get('/invoice/{publicId}/pay', [PublicInvoiceController::class, 'pay'])->name('public-invoice.pay');
Route::post('/webhook/public-invoice/paystack', [PublicInvoiceController::class, 'webhook'])->name('public-invoice.webhook');

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
