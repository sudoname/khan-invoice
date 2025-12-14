#!/bin/bash

# Fix Staging Migrations and Schema
# This ensures all migrations are run on staging

echo "==================================================="
echo "Fixing Staging Database Schema"
echo "==================================================="
echo ""

cd /var/www/staging.kinvoice.ng

echo "1. Checking current migrations status..."
php artisan migrate:status

echo ""
echo "2. Running pending migrations..."
php artisan migrate --force

echo ""
echo "3. Verifying database structure..."
php artisan tinker --execute="
echo 'Users: ' . App\Models\User::count() . PHP_EOL;
echo 'Customers: ' . App\Models\Customer::count() . PHP_EOL;
echo 'Invoices: ' . App\Models\Invoice::count() . PHP_EOL;
"

echo ""
echo "4. Testing Dashboard Controller..."
php artisan tinker <<'EOF'
$user = App\Models\User::find(2);
if ($user) {
    $request = new \Illuminate\Http\Request();
    $request->setUserResolver(fn() => $user);
    try {
        $controller = new App\Http\Controllers\Api\V1\DashboardController();
        $response = $controller->index($request);
        echo "✓ Dashboard works! Response length: " . strlen($response->getContent()) . " bytes\n";
    } catch (\Exception $e) {
        echo "✗ Dashboard ERROR: " . $e->getMessage() . "\n";
        echo "   at " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
} else {
    echo "✗ User ID 2 not found\n";
}
exit
EOF

echo ""
echo "5. Testing Customers Endpoint..."
php artisan tinker --execute="
\$user = App\Models\User::find(2);
if (\$user) {
    \$customers = App\Models\Customer::where('user_id', \$user->id)->get();
    echo '✓ Found ' . \$customers->count() . ' customers for user ' . \$user->email . PHP_EOL;
} else {
    echo '✗ User ID 2 not found' . PHP_EOL;
}
"

echo ""
echo "==================================================="
echo "Fix Complete!"
echo "==================================================="
echo ""
echo "If you still see errors above, please share them."
echo ""
