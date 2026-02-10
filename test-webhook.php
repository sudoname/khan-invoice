<?php

/**
 * Quick test script to verify WhatsApp webhook configuration
 * Run on production server: php test-webhook.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== WhatsApp Webhook Configuration Test ===\n\n";

// Test 1: Check .env configuration
echo "1. Checking .env configuration...\n";
$verifyToken = config('whatsapp.meta.verify_token');
$baseUrl = config('whatsapp.meta.base_url');

if (empty($verifyToken)) {
    echo "   ❌ WHATSAPP_VERIFY_TOKEN is not set in .env\n";
    echo "   Fix: Add WHATSAPP_VERIFY_TOKEN=super-secret-token to .env\n\n";
} else {
    echo "   ✓ WHATSAPP_VERIFY_TOKEN is set: " . str_repeat('*', strlen($verifyToken) - 4) . substr($verifyToken, -4) . "\n\n";
}

// Test 2: Check route exists
echo "2. Checking if webhook route exists...\n";
try {
    $route = app('router')->getRoutes()->getByName('whatsapp.webhook.verify');
    if ($route) {
        echo "   ✓ Webhook route exists: " . $route->uri() . "\n\n";
    } else {
        echo "   ❌ Webhook route 'whatsapp.webhook.verify' not found\n";
        echo "   Fix: Run 'php artisan route:clear'\n\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error checking routes: " . $e->getMessage() . "\n\n";
}

// Test 3: Test webhook URL
echo "3. Testing webhook URL accessibility...\n";
$webhookUrl = url('/api/webhooks/whatsapp');
echo "   Webhook URL: {$webhookUrl}\n";

$testUrl = $webhookUrl . "?hub.mode=subscribe&hub.verify_token={$verifyToken}&hub.challenge=test123";

echo "   Test URL: {$testUrl}\n";
echo "   Run this in browser or curl to test:\n";
echo "   curl -X GET \"{$testUrl}\"\n";
echo "   Expected response: test123\n\n";

// Test 4: Check database tables
echo "4. Checking database tables...\n";
$tables = [
    'wa_accounts',
    'wa_contacts',
    'wa_conversations',
    'wa_messages',
    'leads',
    'automation_rules',
    'automation_logs',
    'wa_opt_outs',
];

foreach ($tables as $table) {
    try {
        if (Schema::hasTable($table)) {
            echo "   ✓ Table '{$table}' exists\n";
        } else {
            echo "   ❌ Table '{$table}' missing\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Error checking table '{$table}': " . $e->getMessage() . "\n";
    }
}

echo "\n5. Checking invoice table columns...\n";
$columns = [
    'wa_conversation_id',
    'wa_contact_id',
    'whatsapp_last_followup_at',
    'whatsapp_followup_attempts',
];

foreach ($columns as $column) {
    try {
        if (Schema::hasColumn('invoices', $column)) {
            echo "   ✓ Column 'invoices.{$column}' exists\n";
        } else {
            echo "   ❌ Column 'invoices.{$column}' missing\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Error checking column '{$column}': " . $e->getMessage() . "\n";
    }
}

echo "\n=== Test Complete ===\n\n";

echo "Next Steps:\n";
echo "1. Ensure WHATSAPP_VERIFY_TOKEN is set in .env\n";
echo "2. Run: php artisan config:clear\n";
echo "3. Run: php artisan route:clear\n";
echo "4. Test the webhook URL in browser\n";
echo "5. Configure webhook in Meta Business Manager\n";
echo "   - URL: {$webhookUrl}\n";
echo "   - Token: {$verifyToken}\n\n";
