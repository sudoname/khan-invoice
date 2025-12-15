#!/bin/bash

# Test Customers API
cd /var/www/staging.kinvoice.ng

echo "Testing Customers API..."
echo ""

echo "1. Check customers in database..."
php artisan tinker --execute="
\$user = App\Models\User::find(2);
echo 'User: ' . \$user->email . PHP_EOL;
echo 'Customers: ' . App\Models\Customer::where('user_id', \$user->id)->count() . PHP_EOL;
"

echo ""
echo "2. Create test token and test API..."
php artisan tinker --execute="
\$user = App\Models\User::find(2);
\$token = \$user->createToken('test')->plainTextToken;
file_put_contents('/tmp/token.txt', \$token);
echo \$token . PHP_EOL;
"

if [ -f "/tmp/token.txt" ]; then
    TOKEN=$(cat /tmp/token.txt)
    echo ""
    echo "3. Testing GET /api/v1/customers..."
    curl -s https://staging.kinvoice.ng/api/v1/customers \
        -H "Authorization: Bearer $TOKEN" \
        -H "Accept: application/json" | head -c 500
    rm -f /tmp/token.txt
fi

echo ""
echo ""
echo "4. Test internally..."
php artisan tinker --execute="
\$user = App\Models\User::find(2);
\$request = new \Illuminate\Http\Request();
\$request->setUserResolver(fn() => \$user);
try {
    \$controller = new App\Http\Controllers\Api\V1\CustomerController();
    \$response = \$controller->index(\$request);
    \$content = \$response->toResponse(\$request)->getContent();
    echo 'SUCCESS: ' . strlen(\$content) . ' bytes' . PHP_EOL;
    echo substr(\$content, 0, 300) . '...' . PHP_EOL;
} catch (\Exception \$e) {
    echo 'ERROR: ' . \$e->getMessage() . PHP_EOL;
}
"
