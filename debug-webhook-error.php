<?php

/**
 * Debug webhook 500 error
 * Run on production: php debug-webhook-error.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Debugging WhatsApp Webhook 500 Error ===\n\n";

// Test 1: Check if WhatsAppService can be instantiated
echo "1. Testing WhatsAppService instantiation...\n";
try {
    $service = app(\App\Services\WhatsApp\WhatsAppService::class);
    echo "   ✓ WhatsAppService created successfully\n\n";
} catch (Exception $e) {
    echo "   ❌ Error creating WhatsAppService: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
}

// Test 2: Check if WhatsAppClientInterface is bound
echo "2. Testing WhatsAppClientInterface binding...\n";
try {
    $client = app(\App\Services\WhatsApp\Contracts\WhatsAppClientInterface::class);
    echo "   ✓ WhatsAppClientInterface resolved: " . get_class($client) . "\n\n";
} catch (Exception $e) {
    echo "   ❌ Error resolving WhatsAppClientInterface: " . $e->getMessage() . "\n";
    echo "   This is likely the problem!\n";
    echo "   Fix: Add service provider binding\n\n";
}

// Test 3: Check if controller can be created
echo "3. Testing WhatsAppWebhookController instantiation...\n";
try {
    $controller = app(\App\Http\Controllers\WhatsApp\WhatsAppWebhookController::class);
    echo "   ✓ WhatsAppWebhookController created successfully\n\n";
} catch (Exception $e) {
    echo "   ❌ Error creating WhatsAppWebhookController: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
}

// Test 4: Simulate webhook request
echo "4. Simulating webhook GET request...\n";
try {
    $request = Illuminate\Http\Request::create(
        '/api/webhooks/whatsapp',
        'GET',
        [
            'hub.mode' => 'subscribe',
            'hub.verify_token' => config('whatsapp.meta.verify_token'),
            'hub.challenge' => 'test123',
        ]
    );

    $controller = app(\App\Http\Controllers\WhatsApp\WhatsAppWebhookController::class);
    $response = $controller->verify($request);

    echo "   ✓ Webhook verify() executed successfully\n";
    echo "   Response status: " . $response->getStatusCode() . "\n";
    echo "   Response content: " . $response->getContent() . "\n\n";
} catch (Exception $e) {
    echo "   ❌ Error executing verify(): " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Stack trace:\n";
    echo "   " . str_replace("\n", "\n   ", $e->getTraceAsString()) . "\n\n";
}

echo "=== Debug Complete ===\n\n";

echo "Check the Laravel log for more details:\n";
echo "tail -50 storage/logs/laravel.log\n\n";
