#!/bin/bash

# Test Dashboard API and Show Exact Error
# Run this on staging to see what error the dashboard returns

echo "=========================================="
echo "Testing Dashboard API"
echo "=========================================="
echo ""

cd /var/www/staging.kinvoice.ng

echo "Step 1: Finding a test user with a token..."
php artisan tinker <<'EOF'
$user = App\Models\User::whereNotNull('email_verified_at')->where('api_enabled', true)->first();

if (!$user) {
    echo "✗ No user found with API enabled\n";
    echo "Creating test token for user ID 2...\n";
    $user = App\Models\User::find(2);
    if ($user) {
        $user->update(['api_enabled' => true]);
    }
}

if ($user) {
    echo "✓ Using user: " . $user->email . " (ID: " . $user->id . ")\n";

    // Create a test token
    $token = $user->createToken('test-token')->plainTextToken;
    echo "\nTest Token: " . $token . "\n";

    // Save token to file for curl test
    file_put_contents('/tmp/test-token.txt', $token);
} else {
    echo "✗ No users found\n";
}
exit
EOF

echo ""
echo "Step 2: Testing dashboard API with authentication..."
echo "----------------------------------------------------"

if [ -f "/tmp/test-token.txt" ]; then
    TOKEN=$(cat /tmp/test-token.txt)

    echo "Making authenticated request to dashboard..."
    echo ""

    HTTP_CODE=$(curl -s -o /tmp/dashboard-response.txt -w "%{http_code}" \
        https://staging.kinvoice.ng/api/v1/dashboard \
        -H "Authorization: Bearer $TOKEN" \
        -H "Accept: application/json")

    echo "HTTP Status Code: $HTTP_CODE"
    echo ""

    if [ "$HTTP_CODE" = "200" ]; then
        echo "✓ Dashboard API works!"
        echo "Response:"
        cat /tmp/dashboard-response.txt | head -c 500
        echo "..."
    else
        echo "✗ Dashboard returned error $HTTP_CODE"
        echo ""
        echo "Full Response:"
        cat /tmp/dashboard-response.txt
    fi

    # Clean up
    rm -f /tmp/test-token.txt /tmp/dashboard-response.txt
else
    echo "✗ Could not get test token"
fi

echo ""
echo ""
echo "Step 3: Testing dashboard internally with Laravel..."
echo "----------------------------------------------------"
php artisan tinker <<'EOF'
$user = App\Models\User::find(2);
if (!$user) {
    echo "✗ User ID 2 not found\n";
    exit;
}

$request = new \Illuminate\Http\Request();
$request->setUserResolver(fn() => $user);

echo "Testing dashboard for: " . $user->email . "\n\n";

try {
    $controller = new App\Http\Controllers\Api\V1\DashboardController();
    $response = $controller->index($request);
    $content = $response->getContent();

    echo "✓ Dashboard controller works!\n";
    echo "Response length: " . strlen($content) . " bytes\n";
    echo "Response preview:\n";
    echo substr($content, 0, 500) . "...\n";
} catch (\Exception $e) {
    echo "✗ Dashboard ERROR:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
exit
EOF

echo ""
echo "=========================================="
echo "Test Complete"
echo "=========================================="
