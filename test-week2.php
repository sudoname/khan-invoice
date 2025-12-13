<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Notifications\PaymentReceivedNotification;
use App\Notifications\InvoiceSentNotification;
use App\Notifications\PaymentReminderNotification;
use App\Notifications\InvoiceOverdueNotification;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║   KHAN INVOICE - WEEK 2 NOTIFICATION SYSTEM TEST         ║\n";
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
echo "1. NOTIFICATION CLASS TESTS\n";
echo "═══════════════════════════════════════════════════════════\n";

test("PaymentReceivedNotification class exists", function() {
    return class_exists(PaymentReceivedNotification::class);
});

test("InvoiceSentNotification class exists", function() {
    return class_exists(InvoiceSentNotification::class);
});

test("PaymentReminderNotification class exists", function() {
    return class_exists(PaymentReminderNotification::class);
});

test("InvoiceOverdueNotification class exists", function() {
    return class_exists(InvoiceOverdueNotification::class);
});

test("SmsChannel class exists", function() {
    return class_exists(SmsChannel::class);
});

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "2. NOTIFICATION STRUCTURE TESTS\n";
echo "═══════════════════════════════════════════════════════════\n";

// Get test user and invoice
$testUser = User::first();
$testInvoice = null;
$testCustomer = null;
$testPayment = null;

if ($testUser) {
    $testCustomer = Customer::where('user_id', $testUser->id)->first();
    if ($testCustomer) {
        $testInvoice = Invoice::where('user_id', $testUser->id)
            ->where('customer_id', $testCustomer->id)
            ->first();

        if ($testInvoice) {
            $testPayment = $testInvoice->payments()->first();
            if (!$testPayment) {
                // Create a test payment
                $testPayment = Payment::create([
                    'invoice_id' => $testInvoice->id,
                    'amount' => 1000.00,
                    'payment_date' => now(),
                    'payment_method' => 'test',
                    'reference_number' => 'TEST-' . time(),
                    'notes' => 'Test payment for notification testing',
                ]);
            }
        }
    }
}

if ($testUser && $testCustomer && $testInvoice && $testPayment) {
    echo "Using test data:\n";
    echo "  User: {$testUser->name}\n";
    echo "  Customer: {$testCustomer->name}\n";
    echo "  Invoice: {$testInvoice->invoice_number}\n\n";

    test("PaymentReceivedNotification has via() method", function() {
        return method_exists(PaymentReceivedNotification::class, 'via');
    });

    test("PaymentReceivedNotification has toMail() method", function() {
        return method_exists(PaymentReceivedNotification::class, 'toMail');
    });

    test("PaymentReceivedNotification has toSms() method", function() {
        return method_exists(PaymentReceivedNotification::class, 'toSms');
    });

    test("PaymentReceivedNotification has toArray() method", function() {
        return method_exists(PaymentReceivedNotification::class, 'toArray');
    });

    test("InvoiceSentNotification has all required methods", function() {
        return method_exists(InvoiceSentNotification::class, 'via') &&
               method_exists(InvoiceSentNotification::class, 'toMail') &&
               method_exists(InvoiceSentNotification::class, 'toSms') &&
               method_exists(InvoiceSentNotification::class, 'toArray');
    });

    test("PaymentReminderNotification has all required methods", function() {
        return method_exists(PaymentReminderNotification::class, 'via') &&
               method_exists(PaymentReminderNotification::class, 'toMail') &&
               method_exists(PaymentReminderNotification::class, 'toSms') &&
               method_exists(PaymentReminderNotification::class, 'toArray');
    });

    test("InvoiceOverdueNotification has all required methods", function() {
        return method_exists(InvoiceOverdueNotification::class, 'via') &&
               method_exists(InvoiceOverdueNotification::class, 'toMail') &&
               method_exists(InvoiceOverdueNotification::class, 'toSms') &&
               method_exists(InvoiceOverdueNotification::class, 'toArray');
    });

    echo "\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "3. NOTIFICATION INSTANTIATION TESTS\n";
    echo "═══════════════════════════════════════════════════════════\n";

    test("Can instantiate PaymentReceivedNotification", function() use ($testPayment, $testInvoice) {
        $notification = new PaymentReceivedNotification($testPayment, $testInvoice);
        return $notification !== null;
    });

    test("Can instantiate InvoiceSentNotification", function() use ($testInvoice) {
        $notification = new InvoiceSentNotification($testInvoice);
        return $notification !== null;
    });

    test("Can instantiate PaymentReminderNotification", function() use ($testInvoice) {
        $notification = new PaymentReminderNotification($testInvoice, 3);
        return $notification !== null;
    });

    test("Can instantiate InvoiceOverdueNotification", function() use ($testInvoice) {
        $notification = new InvoiceOverdueNotification($testInvoice, 5);
        return $notification !== null;
    });

    echo "\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "4. NOTIFICATION CHANNEL LOGIC TESTS\n";
    echo "═══════════════════════════════════════════════════════════\n";

    // Ensure user has preferences
    $preferences = $testUser->notificationPreferences;
    if (!$preferences) {
        $preferences = $testUser->notificationPreferences()->create([
            'sms_enabled' => false,
            'sms_credits_remaining' => 0,
            'email_payment_received' => true,
            'email_invoice_sent' => true,
            'email_payment_reminder' => true,
            'email_invoice_overdue' => true,
        ]);
    }

    test("via() returns channels based on preferences (email only)", function() use ($testCustomer, $testPayment, $testInvoice, $preferences) {
        $preferences->update(['sms_enabled' => false, 'email_payment_received' => true]);
        $notification = new PaymentReceivedNotification($testPayment, $testInvoice);
        $channels = $notification->via($testCustomer);
        return in_array('mail', $channels) && in_array('database', $channels);
    });

    test("via() includes SMS channel when enabled with credits", function() use ($testCustomer, $testPayment, $testInvoice, $preferences) {
        $preferences->update([
            'sms_enabled' => true,
            'sms_credits_remaining' => 5,
            'sms_payment_received' => true
        ]);
        $notification = new PaymentReceivedNotification($testPayment, $testInvoice);
        $channels = $notification->via($testCustomer);
        return in_array(SmsChannel::class, $channels);
    });

    test("toSms() returns string message", function() use ($testCustomer, $testPayment, $testInvoice) {
        $notification = new PaymentReceivedNotification($testPayment, $testInvoice);
        $message = $notification->toSms($testCustomer);
        return is_string($message) && strlen($message) > 0;
    });

    test("toSms() message is within SMS length limits (160 chars)", function() use ($testCustomer, $testPayment, $testInvoice) {
        $notification = new PaymentReceivedNotification($testPayment, $testInvoice);
        $message = $notification->toSms($testCustomer);
        echo "\n   SMS Length: " . strlen($message) . " chars\n   ";
        return strlen($message) <= 160;
    });

    test("toMail() returns MailMessage object", function() use ($testCustomer, $testPayment, $testInvoice) {
        $notification = new PaymentReceivedNotification($testPayment, $testInvoice);
        $mailMessage = $notification->toMail($testCustomer);
        return $mailMessage instanceof \Illuminate\Notifications\Messages\MailMessage;
    });

    test("toArray() returns array with expected keys", function() use ($testCustomer, $testPayment, $testInvoice) {
        $notification = new PaymentReceivedNotification($testPayment, $testInvoice);
        $data = $notification->toArray($testCustomer);
        return is_array($data) &&
               isset($data['type']) &&
               isset($data['invoice_id']) &&
               isset($data['message']);
    });

    echo "\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "5. SMS CHANNEL TESTS\n";
    echo "═══════════════════════════════════════════════════════════\n";

    test("SmsChannel has send() method", function() {
        return method_exists(SmsChannel::class, 'send');
    });

    test("SmsChannel send() method signature is correct", function() {
        $reflection = new \ReflectionMethod(SmsChannel::class, 'send');
        $params = $reflection->getParameters();
        return count($params) === 2; // notifiable and notification
    });

    echo "\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "6. INTEGRATION TESTS\n";
    echo "═══════════════════════════════════════════════════════════\n";

    test("PaymentController has PaymentReceivedNotification import", function() {
        $content = file_get_contents(__DIR__ . '/app/Http/Controllers/PaymentController.php');
        return strpos($content, 'use App\Notifications\PaymentReceivedNotification') !== false;
    });

    test("TestNotifications command exists", function() {
        return class_exists(\App\Console\Commands\TestNotifications::class);
    });

    test("TestNotifications command has correct signature", function() {
        $command = new \App\Console\Commands\TestNotifications();
        $reflection = new \ReflectionClass($command);
        $property = $reflection->getProperty('signature');
        $property->setAccessible(true);
        $signature = $property->getValue($command);
        return strpos($signature, 'notifications:test') !== false;
    });

} else {
    echo "⚠️  Skipping notification tests - missing test data (user, customer, or invoice)\n\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "TEST SUMMARY\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "✅ Passed: $passed\n";
echo "❌ Failed: $failed\n";
echo "Total Tests: " . ($passed + $failed) . "\n";

if ($failed === 0) {
    echo "\n🎉 ALL TESTS PASSED! Week 2 implementation is complete.\n";
} else {
    echo "\n⚠️  Some tests failed. Please review the errors above.\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "NEXT STEPS:\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "1. Test notifications manually:\n";
echo "   php artisan notifications:test {invoice_id}\n";
echo "2. Process the queue:\n";
echo "   php artisan queue:work --once\n";
echo "3. Check sms_logs table:\n";
echo "   SELECT * FROM sms_logs ORDER BY created_at DESC LIMIT 5;\n";
echo "4. Check notifications table:\n";
echo "   SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5;\n";
echo "5. Continue to Week 3: Automatic Reminders & Scheduler\n";
echo "═══════════════════════════════════════════════════════════\n";
