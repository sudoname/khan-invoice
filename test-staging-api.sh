#!/bin/bash

# Test Staging API Endpoints
# Run this on the staging server to test what the API returns

echo "==================================================="
echo "Testing Staging API Endpoints"
echo "==================================================="
echo ""

cd /var/www/staging.kinvoice.ng

echo "1. Testing Dashboard Endpoint..."
echo "---------------------------------------------------"
php artisan tinker <<'EOF'
$user = App\Models\User::find(2); // Your Google Sign-In user
if (!$user) {
    echo "Error: User ID 2 not found!\n";
    exit;
}

// Simulate the dashboard API call
$request = new \Illuminate\Http\Request();
$request->setUserResolver(function () use ($user) {
    return $user;
});

try {
    $controller = new App\Http\Controllers\Api\V1\DashboardController();
    $response = $controller->index($request);
    echo "✓ Dashboard endpoint works!\n";
    echo "Response: " . $response->getContent() . "\n";
} catch (\Exception $e) {
    echo "✗ Dashboard endpoint ERROR:\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
exit
EOF

echo ""
echo "2. Testing Customers Endpoint..."
echo "---------------------------------------------------"
php artisan tinker <<'EOF'
$user = App\Models\User::find(2);
if (!$user) {
    echo "Error: User ID 2 not found!\n";
    exit;
}

try {
    $customers = App\Models\Customer::where('user_id', $user->id)->get();
    echo "✓ Found " . $customers->count() . " customers\n";
    foreach ($customers as $customer) {
        echo "  - " . $customer->name . " (" . $customer->email . ")\n";
    }

    // Test API response format
    $response = $customers->map(function($c) {
        return [
            'id' => $c->id,
            'name' => $c->name,
            'email' => $c->email,
            'phone' => $c->phone,
        ];
    });
    echo "\nAPI Response: " . json_encode(['data' => $response]) . "\n";
} catch (\Exception $e) {
    echo "✗ Customers query ERROR:\n";
    echo "Error: " . $e->getMessage() . "\n";
}
exit
EOF

echo ""
echo "3. Checking Database Schema..."
echo "---------------------------------------------------"
php artisan tinker <<'EOF'
echo "Invoices table columns:\n";
$columns = DB::select("PRAGMA table_info(invoices)");
foreach ($columns as $col) {
    echo "  - " . $col->name . " (" . $col->type . ")\n";
}
exit
EOF

echo ""
echo "==================================================="
echo "Test Complete"
echo "==================================================="
