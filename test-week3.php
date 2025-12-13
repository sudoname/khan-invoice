<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Jobs\SendPaymentReminderJob;
use App\Jobs\SendOverdueNotificationJob;
use Illuminate\Support\Facades\Artisan;

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║   KHAN INVOICE - WEEK 3 SCHEDULER & JOBS TEST            ║\n";
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
echo "1. JOB CLASS TESTS\n";
echo "═══════════════════════════════════════════════════════════\n";

test("SendPaymentReminderJob class exists", function() {
    return class_exists(SendPaymentReminderJob::class);
});

test("SendOverdueNotificationJob class exists", function() {
    return class_exists(SendOverdueNotificationJob::class);
});

test("SendPaymentReminderJob implements ShouldQueue", function() {
    return in_array(
        'Illuminate\Contracts\Queue\ShouldQueue',
        class_implements(SendPaymentReminderJob::class)
    );
});

test("SendOverdueNotificationJob implements ShouldQueue", function() {
    return in_array(
        'Illuminate\Contracts\Queue\ShouldQueue',
        class_implements(SendOverdueNotificationJob::class)
    );
});

test("SendPaymentReminderJob has correct retry configuration", function() {
    $reflection = new \ReflectionClass(SendPaymentReminderJob::class);
    $triesProperty = $reflection->getProperty('tries');
    $triesProperty->setAccessible(true);
    return $triesProperty->getDefaultValue() === 3;
});

test("SendPaymentReminderJob has backoff configuration", function() {
    $reflection = new \ReflectionClass(SendPaymentReminderJob::class);
    $backoffProperty = $reflection->getProperty('backoff');
    $backoffProperty->setAccessible(true);
    return $backoffProperty->getDefaultValue() === 60;
});

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "2. CONSOLE COMMAND TESTS\n";
echo "═══════════════════════════════════════════════════════════\n";

test("SendPaymentReminders command exists", function() {
    return class_exists(\App\Console\Commands\SendPaymentReminders::class);
});

test("CheckOverdueInvoices command exists", function() {
    return class_exists(\App\Console\Commands\CheckOverdueInvoices::class);
});

test("SendPaymentReminders command is registered", function() {
    $commands = Artisan::all();
    return isset($commands['reminders:send-payment']);
});

test("CheckOverdueInvoices command is registered", function() {
    $commands = Artisan::all();
    return isset($commands['reminders:check-overdue']);
});

test("SendPaymentReminders command has correct signature", function() {
    $command = new \App\Console\Commands\SendPaymentReminders();
    $reflection = new \ReflectionClass($command);
    $property = $reflection->getProperty('signature');
    $property->setAccessible(true);
    return $property->getValue($command) === 'reminders:send-payment';
});

test("CheckOverdueInvoices command has correct signature", function() {
    $command = new \App\Console\Commands\CheckOverdueInvoices();
    $reflection = new \ReflectionClass($command);
    $property = $reflection->getProperty('signature');
    $property->setAccessible(true);
    return $property->getValue($command) === 'reminders:check-overdue';
});

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "3. SCHEDULER CONFIGURATION TESTS\n";
echo "═══════════════════════════════════════════════════════════\n";

test("bootstrap/app.php has scheduler configuration", function() {
    $content = file_get_contents(__DIR__ . '/bootstrap/app.php');
    return strpos($content, 'withSchedule') !== false;
});

test("Scheduler includes payment reminder command", function() {
    $content = file_get_contents(__DIR__ . '/bootstrap/app.php');
    return strpos($content, 'reminders:send-payment') !== false;
});

test("Scheduler includes overdue check command", function() {
    $content = file_get_contents(__DIR__ . '/bootstrap/app.php');
    return strpos($content, 'reminders:check-overdue') !== false;
});

test("Payment reminder scheduled for daily execution", function() {
    $content = file_get_contents(__DIR__ . '/bootstrap/app.php');
    return strpos($content, 'dailyAt') !== false;
});

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "4. SCHEDULER LIST TEST\n";
echo "═══════════════════════════════════════════════════════════\n";

echo "Checking scheduled tasks...\n\n";

// Get scheduled tasks
ob_start();
Artisan::call('schedule:list');
$scheduleOutput = Artisan::output();
ob_end_clean();

echo $scheduleOutput . "\n";

test("reminders:send-payment appears in schedule:list", function() use ($scheduleOutput) {
    return strpos($scheduleOutput, 'reminders:send-payment') !== false;
});

test("reminders:check-overdue appears in schedule:list", function() use ($scheduleOutput) {
    return strpos($scheduleOutput, 'reminders:check-overdue') !== false;
});

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "5. COMMAND EXECUTION TESTS\n";
echo "═══════════════════════════════════════════════════════════\n";

test("Can execute reminders:send-payment command", function() {
    try {
        $exitCode = Artisan::call('reminders:send-payment');
        return $exitCode === 0;
    } catch (\Exception $e) {
        echo "\n   Error: " . $e->getMessage() . "\n   ";
        return false;
    }
});

test("Can execute reminders:check-overdue command", function() {
    try {
        $exitCode = Artisan::call('reminders:check-overdue');
        return $exitCode === 0;
    } catch (\Exception $e) {
        echo "\n   Error: " . $e->getMessage() . "\n   ";
        return false;
    }
});

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "6. QUEUE CONFIGURATION TESTS\n";
echo "═══════════════════════════════════════════════════════════\n";

test("Queue connection is database", function() {
    return config('queue.default') === 'database';
});

test("Jobs table exists", function() {
    return \Illuminate\Support\Facades\Schema::hasTable('jobs');
});

test("Failed jobs table exists", function() {
    return \Illuminate\Support\Facades\Schema::hasTable('failed_jobs');
});

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "TEST SUMMARY\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "✅ Passed: $passed\n";
echo "❌ Failed: $failed\n";
echo "Total Tests: " . ($passed + $failed) . "\n";

if ($failed === 0) {
    echo "\n🎉 ALL TESTS PASSED! Week 3 implementation is complete.\n";
} else {
    echo "\n⚠️  Some tests failed. Please review the errors above.\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "NEXT STEPS:\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "1. Create test invoices with various due dates:\n";
echo "   - Invoice due in 3 days\n";
echo "   - Invoice due today\n";
echo "   - Invoice overdue by 1 day\n";
echo "\n";
echo "2. Test commands manually:\n";
echo "   php artisan reminders:send-payment\n";
echo "   php artisan reminders:check-overdue\n";
echo "\n";
echo "3. Process the queue:\n";
echo "   php artisan queue:work --once\n";
echo "\n";
echo "4. View scheduled tasks:\n";
echo "   php artisan schedule:list\n";
echo "\n";
echo "5. Test scheduler manually:\n";
echo "   php artisan schedule:test\n";
echo "   php artisan schedule:run\n";
echo "\n";
echo "6. Set up cron job on production server:\n";
echo "   * * * * * cd /var/www/staging.kinvoice.ng && php artisan schedule:run >> /dev/null 2>&1\n";
echo "\n";
echo "7. Start queue worker as daemon (use supervisor):\n";
echo "   php artisan queue:work --tries=3 --daemon\n";
echo "\n";
echo "8. Continue to Week 4: REST API Implementation\n";
echo "═══════════════════════════════════════════════════════════\n";
