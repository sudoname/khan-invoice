<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\TermiiService;

echo "=== Testing Termii SMS Service ===\n\n";

$service = new TermiiService();

// Test 1: Check balance
echo "1. Checking Termii account balance...\n";
$balance = $service->getBalance();
echo "Balance Result:\n";
print_r($balance);
echo "\n";

// Test 2: Send test SMS
echo "2. Sending test SMS to +2348168166109...\n";
$result = $service->sendSms('+2348168166109', 'Test from Khan Invoice - SMS integration working!');
echo "SMS Result:\n";
print_r($result);
echo "\n";

echo "=== Test Complete ===\n";
