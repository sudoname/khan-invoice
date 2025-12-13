<?php

/**
 * Week 6 Test Suite: WhatsApp Business Integration
 *
 * Tests all WhatsApp integration components:
 * - WhatsApp logs migration
 * - NotificationPreference WhatsApp fields
 * - WhatsAppLog model
 * - WhatsAppService
 * - WhatsAppChannel
 * - Notification classes with toWhatsApp() method
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n";
echo "========================================\n";
echo "  WEEK 6: WHATSAPP INTEGRATION TESTS\n";
echo "========================================\n\n";

$passedTests = 0;
$failedTests = 0;

function test(string $name, callable $callback): void
{
    global $passedTests, $failedTests;

    try {
        $callback();
        echo "✓ $name\n";
        $passedTests++;
    } catch (Exception $e) {
        echo "✗ $name\n";
        echo "  Error: " . $e->getMessage() . "\n";
        $failedTests++;
    }
}

// ========================================
// 1. DATABASE SCHEMA TESTS
// ========================================

echo "1. DATABASE SCHEMA TESTS\n";
echo "------------------------\n";

test('whatsapp_logs table exists', function() {
    $exists = \Illuminate\Support\Facades\Schema::hasTable('whatsapp_logs');
    if (!$exists) throw new Exception('whatsapp_logs table does not exist');
});

test('whatsapp_logs has required columns', function() {
    $columns = [
        'id', 'user_id', 'recipient_phone', 'message_type',
        'message_content', 'status', 'provider_message_id',
        'error_message', 'cost', 'created_at', 'updated_at'
    ];

    foreach ($columns as $column) {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('whatsapp_logs', $column)) {
            throw new Exception("Column '$column' missing from whatsapp_logs table");
        }
    }
});

test('notification_preferences has WhatsApp fields', function() {
    $columns = [
        'whatsapp_enabled', 'whatsapp_credits_remaining',
        'whatsapp_payment_received', 'whatsapp_invoice_sent',
        'whatsapp_payment_reminder', 'whatsapp_invoice_overdue'
    ];

    foreach ($columns as $column) {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('notification_preferences', $column)) {
            throw new Exception("Column '$column' missing from notification_preferences table");
        }
    }
});

echo "\n";

// ========================================
// 2. MODEL TESTS
// ========================================

echo "2. MODEL TESTS\n";
echo "--------------\n";

test('WhatsAppLog model exists', function() {
    if (!class_exists(\App\Models\WhatsAppLog::class)) {
        throw new Exception('WhatsAppLog model does not exist');
    }
});

test('User has whatsAppLogs relationship', function() {
    $user = \App\Models\User::first();
    if (!$user) throw new Exception('No users found in database');

    if (!method_exists($user, 'whatsAppLogs')) {
        throw new Exception('User model missing whatsAppLogs() relationship');
    }
});

test('NotificationPreference has canSendWhatsApp method', function() {
    $user = \App\Models\User::first();
    if (!$user) throw new Exception('No users found');

    $preferences = $user->notificationPreferences;
    if (!$preferences) {
        $preferences = $user->notificationPreferences()->create([]);
    }

    if (!method_exists($preferences, 'canSendWhatsApp')) {
        throw new Exception('NotificationPreference missing canSendWhatsApp() method');
    }
});

test('NotificationPreference has deductWhatsAppCredit method', function() {
    $user = \App\Models\User::first();
    if (!$user) throw new Exception('No users found');

    $preferences = $user->notificationPreferences;
    if (!$preferences) {
        $preferences = $user->notificationPreferences()->create([]);
    }

    if (!method_exists($preferences, 'deductWhatsAppCredit')) {
        throw new Exception('NotificationPreference missing deductWhatsAppCredit() method');
    }
});

echo "\n";

// ========================================
// 3. SERVICE TESTS
// ========================================

echo "3. SERVICE TESTS\n";
echo "----------------\n";

test('WhatsAppService class exists', function() {
    if (!class_exists(\App\Services\WhatsAppService::class)) {
        throw new Exception('WhatsAppService class does not exist');
    }
});

test('WhatsAppService has sendWhatsApp method', function() {
    $service = new \App\Services\WhatsAppService();
    if (!method_exists($service, 'sendWhatsApp')) {
        throw new Exception('WhatsAppService missing sendWhatsApp() method');
    }
});

test('WhatsAppService has getBalance method', function() {
    $service = new \App\Services\WhatsAppService();
    if (!method_exists($service, 'getBalance')) {
        throw new Exception('WhatsAppService missing getBalance() method');
    }
});

test('Twilio configuration exists', function() {
    $config = config('services.twilio');
    if (!$config) {
        throw new Exception('Twilio configuration missing from config/services.php');
    }

    if (!isset($config['account_sid']) || !isset($config['auth_token']) || !isset($config['whatsapp_from'])) {
        throw new Exception('Twilio configuration incomplete');
    }
});

echo "\n";

// ========================================
// 4. NOTIFICATION CHANNEL TESTS
// ========================================

echo "4. NOTIFICATION CHANNEL TESTS\n";
echo "-----------------------------\n";

test('WhatsAppChannel class exists', function() {
    if (!class_exists(\App\Notifications\Channels\WhatsAppChannel::class)) {
        throw new Exception('WhatsAppChannel class does not exist');
    }
});

test('WhatsAppChannel has send method', function() {
    $service = new \App\Services\WhatsAppService();
    $channel = new \App\Notifications\Channels\WhatsAppChannel($service);

    if (!method_exists($channel, 'send')) {
        throw new Exception('WhatsAppChannel missing send() method');
    }
});

echo "\n";

// ========================================
// 5. NOTIFICATION CLASSES TESTS
// ========================================

echo "5. NOTIFICATION CLASSES TESTS\n";
echo "-----------------------------\n";

$notificationClasses = [
    'PaymentReceivedNotification' => \App\Notifications\PaymentReceivedNotification::class,
    'InvoiceSentNotification' => \App\Notifications\InvoiceSentNotification::class,
    'PaymentReminderNotification' => \App\Notifications\PaymentReminderNotification::class,
    'InvoiceOverdueNotification' => \App\Notifications\InvoiceOverdueNotification::class,
];

foreach ($notificationClasses as $name => $class) {
    test("$name has toWhatsApp method", function() use ($class, $name) {
        if (!method_exists($class, 'toWhatsApp')) {
            throw new Exception("$name missing toWhatsApp() method");
        }
    });

    test("$name uses WhatsAppChannel", function() use ($class, $name) {
        $reflection = new \ReflectionClass($class);
        $file = file_get_contents($reflection->getFileName());

        if (strpos($file, 'use App\Notifications\Channels\WhatsAppChannel') === false) {
            throw new Exception("$name not importing WhatsAppChannel");
        }

        if (strpos($file, 'WhatsAppChannel::class') === false) {
            throw new Exception("$name not using WhatsAppChannel in via() method");
        }
    });
}

echo "\n";

// ========================================
// 6. INTEGRATION TESTS (if test data exists)
// ========================================

echo "6. INTEGRATION TESTS\n";
echo "--------------------\n";

test('Can create WhatsApp log entry', function() {
    $user = \App\Models\User::first();
    if (!$user) throw new Exception('No users found');

    $log = \App\Models\WhatsAppLog::create([
        'user_id' => $user->id,
        'recipient_phone' => '+2348012345678',
        'message_type' => 'test',
        'message_content' => 'Test message',
        'status' => 'sent',
        'cost' => 1.0,
    ]);

    if (!$log->id) {
        throw new Exception('Failed to create WhatsApp log');
    }

    // Clean up
    $log->delete();
});

test('Can update notification preferences for WhatsApp', function() {
    $user = \App\Models\User::first();
    if (!$user) throw new Exception('No users found');

    $preferences = $user->notificationPreferences;
    if (!$preferences) {
        $preferences = $user->notificationPreferences()->create([]);
    }

    $preferences->update([
        'whatsapp_enabled' => true,
        'whatsapp_credits_remaining' => 100,
        'whatsapp_payment_received' => true,
        'whatsapp_invoice_sent' => true,
        'whatsapp_payment_reminder' => true,
        'whatsapp_invoice_overdue' => true,
    ]);

    $preferences->refresh();

    if (!$preferences->whatsapp_enabled) {
        throw new Exception('Failed to enable WhatsApp');
    }

    if ($preferences->whatsapp_credits_remaining !== 100) {
        throw new Exception('Failed to set WhatsApp credits');
    }
});

test('canSendWhatsApp() returns correct value', function() {
    $user = \App\Models\User::first();
    if (!$user) throw new Exception('No users found');

    $preferences = $user->notificationPreferences;
    if (!$preferences) {
        $preferences = $user->notificationPreferences()->create([]);
    }

    // Enable WhatsApp with credits
    $preferences->update([
        'whatsapp_enabled' => true,
        'whatsapp_credits_remaining' => 10,
        'whatsapp_payment_received' => true,
    ]);

    if (!$preferences->canSendWhatsApp('payment_received')) {
        throw new Exception('canSendWhatsApp() should return true when enabled with credits');
    }

    // Disable WhatsApp
    $preferences->update(['whatsapp_enabled' => false]);

    if ($preferences->canSendWhatsApp('payment_received')) {
        throw new Exception('canSendWhatsApp() should return false when disabled');
    }

    // Enable but no credits
    $preferences->update([
        'whatsapp_enabled' => true,
        'whatsapp_credits_remaining' => 0,
    ]);

    if ($preferences->canSendWhatsApp('payment_received')) {
        throw new Exception('canSendWhatsApp() should return false with 0 credits');
    }
});

test('deductWhatsAppCredit() works correctly', function() {
    $user = \App\Models\User::first();
    if (!$user) throw new Exception('No users found');

    $preferences = $user->notificationPreferences;
    if (!$preferences) {
        $preferences = $user->notificationPreferences()->create([]);
    }

    $preferences->update(['whatsapp_credits_remaining' => 10]);
    $preferences->deductWhatsAppCredit();
    $preferences->refresh();

    if ($preferences->whatsapp_credits_remaining !== 9) {
        throw new Exception('deductWhatsAppCredit() did not deduct credit correctly');
    }
});

echo "\n";

// ========================================
// SUMMARY
// ========================================

echo "========================================\n";
echo "TEST SUMMARY\n";
echo "========================================\n\n";

$totalTests = $passedTests + $failedTests;
$passRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 1) : 0;

echo "Total Tests: $totalTests\n";
echo "Passed: $passedTests ✓\n";
echo "Failed: $failedTests ✗\n";
echo "Pass Rate: $passRate%\n\n";

if ($failedTests === 0) {
    echo "🎉 ALL TESTS PASSED! Week 6 implementation is complete.\n\n";
    echo "Next Steps:\n";
    echo "1. Configure Twilio credentials in .env file\n";
    echo "2. Test WhatsApp sending in production with real phone numbers\n";
    echo "3. Purchase WhatsApp credits for users\n";
    echo "4. Monitor WhatsApp logs for delivery status\n\n";
} else {
    echo "⚠️  Some tests failed. Please review and fix the issues above.\n\n";
}

echo "========================================\n\n";
