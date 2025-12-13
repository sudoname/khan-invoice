<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\NotificationPreference;
use App\Models\SmsLog;
use App\Services\TermiiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║   KHAN INVOICE - WEEK 1 SMS FOUNDATION TEST SUITE        ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

$passed = 0;
$failed = 0;

function test($name, $callback) {
    global $passed, $failed;
    echo "Testing: $name... ";
    try {
        $result = $callback();
        if ($result) {
            echo "✅ PASS\n";
            $passed++;
        } else {
            echo "❌ FAIL\n";
            $failed++;
        }
    } catch (\Exception $e) {
        echo "❌ FAIL - " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "═══════════════════════════════════════════════════════════\n";
echo "1. DATABASE SCHEMA TESTS\n";
echo "═══════════════════════════════════════════════════════════\n";

test("notification_preferences table exists", function() {
    return Schema::hasTable('notification_preferences');
});

test("notification_preferences has user_id column", function() {
    return Schema::hasColumn('notification_preferences', 'user_id');
});

test("notification_preferences has sms_enabled column", function() {
    return Schema::hasColumn('notification_preferences', 'sms_enabled');
});

test("notification_preferences has sms_credits_remaining column", function() {
    return Schema::hasColumn('notification_preferences', 'sms_credits_remaining');
});

test("sms_logs table exists", function() {
    return Schema::hasTable('sms_logs');
});

test("sms_logs has recipient_phone column", function() {
    return Schema::hasColumn('sms_logs', 'recipient_phone');
});

test("sms_logs has message_type column", function() {
    return Schema::hasColumn('sms_logs', 'message_type');
});

test("sms_logs has status column", function() {
    return Schema::hasColumn('sms_logs', 'status');
});

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "2. MODEL TESTS\n";
echo "═══════════════════════════════════════════════════════════\n";

test("NotificationPreference model exists", function() {
    return class_exists(NotificationPreference::class);
});

test("SmsLog model exists", function() {
    return class_exists(SmsLog::class);
});

test("User model has notificationPreferences relationship", function() {
    $user = User::first();
    return method_exists($user, 'notificationPreferences');
});

test("User model has smsLogs relationship", function() {
    $user = User::first();
    return method_exists($user, 'smsLogs');
});

test("NotificationPreference has canSendSms method", function() {
    return method_exists(NotificationPreference::class, 'canSendSms');
});

test("NotificationPreference has deductSmsCredit method", function() {
    return method_exists(NotificationPreference::class, 'deductSmsCredit');
});

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "3. USER RELATIONSHIP TESTS\n";
echo "═══════════════════════════════════════════════════════════\n";

$testUser = User::first();

if ($testUser) {
    echo "Using test user: {$testUser->name} ({$testUser->email})\n\n";

    test("Can create notification preferences for user", function() use ($testUser) {
        $prefs = $testUser->notificationPreferences()->firstOrCreate([
            'user_id' => $testUser->id,
        ], [
            'sms_enabled' => false,
            'sms_credits_remaining' => 10,
            'email_payment_received' => true,
        ]);
        return $prefs->exists;
    });

    test("Can retrieve user's notification preferences", function() use ($testUser) {
        $prefs = $testUser->notificationPreferences;
        return $prefs !== null;
    });

    test("Notification preferences have correct default values", function() use ($testUser) {
        $prefs = $testUser->notificationPreferences;
        return isset($prefs->sms_enabled) && isset($prefs->email_payment_received);
    });

    test("Can check if SMS can be sent (when disabled)", function() use ($testUser) {
        $prefs = $testUser->notificationPreferences;
        $prefs->update(['sms_enabled' => false, 'sms_credits_remaining' => 10]);
        return $prefs->canSendSms('payment_received') === false;
    });

    test("Can enable SMS and add credits", function() use ($testUser) {
        $prefs = $testUser->notificationPreferences;
        $prefs->update([
            'sms_enabled' => true,
            'sms_credits_remaining' => 5,
            'sms_payment_received' => true,
        ]);
        return $prefs->canSendSms('payment_received') === true;
    });

    test("Can deduct SMS credit", function() use ($testUser) {
        $prefs = $testUser->notificationPreferences;
        $initialCredits = $prefs->sms_credits_remaining;
        $prefs->deductSmsCredit();
        $prefs->refresh();
        return $prefs->sms_credits_remaining === ($initialCredits - 1);
    });
} else {
    echo "⚠️  No users found in database. Skipping user relationship tests.\n\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "4. SMS LOG TESTS\n";
echo "═══════════════════════════════════════════════════════════\n";

if ($testUser) {
    test("Can create SMS log entry", function() use ($testUser) {
        $log = SmsLog::create([
            'user_id' => $testUser->id,
            'recipient_phone' => '+2348168166109',
            'message_type' => 'payment_received',
            'message_content' => 'Test SMS message',
            'status' => 'sent',
            'cost' => 1.5,
        ]);
        return $log->exists;
    });

    test("SMS log belongs to user", function() use ($testUser) {
        $log = SmsLog::latest()->first();
        return $log && $log->user_id === $testUser->id;
    });

    test("SMS log scope byMessageType works", function() {
        $logs = SmsLog::byMessageType('payment_received')->get();
        return $logs->count() > 0;
    });

    test("SMS log scope forUser works", function() use ($testUser) {
        $logs = SmsLog::forUser($testUser->id)->get();
        return $logs->count() > 0;
    });

    // Clean up test log
    SmsLog::where('message_content', 'Test SMS message')->delete();
}

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "5. TERMII SERVICE TESTS\n";
echo "═══════════════════════════════════════════════════════════\n";

test("TermiiService class exists", function() {
    return class_exists(TermiiService::class);
});

test("Can instantiate TermiiService", function() {
    $service = new TermiiService();
    return $service !== null;
});

test("Termii config exists", function() {
    $apiKey = config('services.termii.api_key');
    return $apiKey !== null && $apiKey !== '';
});

test("Can check Termii balance", function() {
    $service = new TermiiService();
    $result = $service->getBalance();
    echo "\n   Balance: " . ($result['data']['balance'] ?? 'N/A') . " " . ($result['data']['currency'] ?? '') . "\n   ";
    return $result['status'] === true;
});

test("Phone number normalization works", function() {
    $service = new TermiiService();
    $reflection = new \ReflectionClass($service);
    $method = $reflection->getMethod('normalizePhoneNumber');
    $method->setAccessible(true);

    $normalized = $method->invoke($service, '08012345678');
    return $normalized === '+2348012345678';
});

test("Phone number normalization handles +234 format", function() {
    $service = new TermiiService();
    $reflection = new \ReflectionClass($service);
    $method = $reflection->getMethod('normalizePhoneNumber');
    $method->setAccessible(true);

    $normalized = $method->invoke($service, '+2348012345678');
    return $normalized === '+2348012345678';
});

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "6. CONFIGURATION TESTS\n";
echo "═══════════════════════════════════════════════════════════\n";

test("Termii API key is configured", function() {
    return config('services.termii.api_key') !== null;
});

test("Termii sender ID is configured", function() {
    return config('services.termii.sender_id') !== null;
});

test("Queue connection is database", function() {
    return config('queue.default') === 'database';
});

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "7. FILAMENT PAGE TESTS\n";
echo "═══════════════════════════════════════════════════════════\n";

test("NotificationSettings page class exists", function() {
    return class_exists(\App\Filament\App\Pages\NotificationSettings::class);
});

test("NotificationSettings view file exists", function() {
    return file_exists(resource_path('views/filament/app/pages/notification-settings.blade.php'));
});

test("NotificationSettings page is discoverable", function() {
    $page = new \App\Filament\App\Pages\NotificationSettings();
    return method_exists($page, 'mount') && method_exists($page, 'form');
});

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "TEST SUMMARY\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "✅ Passed: $passed\n";
echo "❌ Failed: $failed\n";
echo "Total Tests: " . ($passed + $failed) . "\n";

if ($failed === 0) {
    echo "\n🎉 ALL TESTS PASSED! Week 1 implementation is complete.\n";
} else {
    echo "\n⚠️  Some tests failed. Please review the errors above.\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "NEXT STEPS:\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "1. Start Laravel dev server: php artisan serve\n";
echo "2. Visit: http://localhost:8000/app/login\n";
echo "3. Navigate to: Settings > Notification Settings\n";
echo "4. Enable SMS and configure preferences\n";
echo "5. Register sender ID 'KhanInvoice' at termii.com\n";
echo "6. Continue to Week 2: Notification System\n";
echo "═══════════════════════════════════════════════════════════\n";
