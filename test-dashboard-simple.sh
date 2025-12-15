#!/bin/bash

# Simple Dashboard Test
cd /var/www/staging.kinvoice.ng

echo "Testing Dashboard API..."
echo ""

# Test 1: Check users
echo "1. Checking users..."
php artisan tinker --execute="
echo 'Total users: ' . App\Models\User::count() . PHP_EOL;
echo 'Verified users: ' . App\Models\User::whereNotNull('email_verified_at')->count() . PHP_EOL;
\$user = App\Models\User::find(2);
if (\$user) {
    echo 'User ID 2: ' . \$user->email . ' (API Enabled: ' . (\$user->api_enabled ? 'Yes' : 'No') . ')' . PHP_EOL;
} else {
    echo 'User ID 2 not found' . PHP_EOL;
}
"

echo ""
echo "2. Creating test token..."
php artisan tinker --execute="
\$user = App\Models\User::find(2);
if (\$user) {
    \$user->update(['api_enabled' => true]);
    \$token = \$user->createToken('mobile-test')->plainTextToken;
    echo \$token . PHP_EOL;
    file_put_contents('/tmp/token.txt', \$token);
} else {
    echo 'ERROR: No user found' . PHP_EOL;
}
"

echo ""
echo "3. Testing dashboard with token..."
if [ -f "/tmp/token.txt" ]; then
    TOKEN=$(cat /tmp/token.txt)
    echo "Token: ${TOKEN:0:20}..."
    echo ""

    curl -v https://staging.kinvoice.ng/api/v1/dashboard \
        -H "Authorization: Bearer $TOKEN" \
        -H "Accept: application/json" 2>&1 | grep -E "(HTTP|< |{)"

    rm -f /tmp/token.txt
fi

echo ""
echo ""
echo "4. Testing dashboard internally..."
php artisan tinker --execute="
\$user = App\Models\User::find(2);
\$request = new \Illuminate\Http\Request();
\$request->setUserResolver(fn() => \$user);
try {
    \$controller = new App\Http\Controllers\Api\V1\DashboardController();
    \$response = \$controller->index(\$request);
    echo 'SUCCESS: ' . strlen(\$response->getContent()) . ' bytes' . PHP_EOL;
} catch (\Exception \$e) {
    echo 'ERROR: ' . \$e->getMessage() . PHP_EOL;
    echo 'Line: ' . \$e->getLine() . ' in ' . basename(\$e->getFile()) . PHP_EOL;
}
"

echo ""
echo "5. Checking recent Laravel errors..."
tail -20 storage/logs/laravel.log | grep ERROR || echo "No recent errors"
