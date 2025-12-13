<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Currency Test ===\n\n";

// Count total currencies
$total = \App\Models\Currency::count();
echo "Total currencies in database: $total\n\n";

// Check key currencies
$keyCurrencies = ['USD', 'EUR', 'GBP', 'NGN', 'GHS', 'INR', 'JPY', 'CAD'];

echo "Key currencies check:\n";
foreach ($keyCurrencies as $code) {
    $currency = \App\Models\Currency::where('code', $code)->first();
    if ($currency) {
        echo "  ✓ $code - {$currency->name} ({$currency->symbol}) - " . ($currency->is_active ? 'Active' : 'Inactive') . "\n";
    } else {
        echo "  ✗ $code - Not found\n";
    }
}

echo "\nAll currencies (sorted by code):\n";
$allCurrencies = \App\Models\Currency::where('is_active', true)
    ->orderBy('code')
    ->get(['code', 'name', 'symbol']);

foreach ($allCurrencies as $currency) {
    echo "  {$currency->code} - {$currency->name} ({$currency->symbol})\n";
}

echo "\n=== Test Complete ===\n";
