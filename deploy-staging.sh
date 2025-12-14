#!/bin/bash

# Staging Deployment Script for Khan Invoice App
# Run this on the staging server to deploy updates

set -e  # Exit on any error

echo "===================================================="
echo "Khan Invoice - Staging Deployment"
echo "===================================================="
echo ""

# Navigate to project directory
cd /var/www/staging.kinvoice.ng

echo "1. Fetching latest code from Git..."
echo "----------------------------------------------------"
git fetch origin
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
echo "Current branch: $CURRENT_BRANCH"
git pull origin $CURRENT_BRANCH
echo "✓ Code updated"
echo ""

echo "2. Installing/Updating dependencies..."
echo "----------------------------------------------------"
composer install --no-dev --optimize-autoloader --no-interaction
echo "✓ Dependencies updated"
echo ""

echo "3. Running database migrations..."
echo "----------------------------------------------------"
php artisan migrate --force
echo "✓ Migrations completed"
echo ""

echo "4. Clearing all caches..."
echo "----------------------------------------------------"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo "✓ Caches cleared"
echo ""

echo "5. Optimizing for production..."
echo "----------------------------------------------------"
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "✓ Optimization complete"
echo ""

echo "6. Restarting PHP-FPM..."
echo "----------------------------------------------------"
# Try different PHP versions
if systemctl is-active --quiet php8.3-fpm; then
    sudo systemctl restart php8.3-fpm
    echo "✓ PHP 8.3 FPM restarted"
elif systemctl is-active --quiet php8.2-fpm; then
    sudo systemctl restart php8.2-fpm
    echo "✓ PHP 8.2 FPM restarted"
elif systemctl is-active --quiet php8.1-fpm; then
    sudo systemctl restart php8.1-fpm
    echo "✓ PHP 8.1 FPM restarted"
else
    echo "⚠ Could not detect PHP-FPM service"
fi
echo ""

echo "7. Verifying deployment..."
echo "----------------------------------------------------"
php artisan tinker --execute="
echo '✓ Users: ' . App\Models\User::count() . PHP_EOL;
echo '✓ Customers: ' . App\Models\Customer::count() . PHP_EOL;
echo '✓ Invoices: ' . App\Models\Invoice::count() . PHP_EOL;
"

# Test Dashboard endpoint
echo ""
echo "Testing Dashboard API..."
php artisan tinker <<'EOF'
$user = App\Models\User::find(2);
if ($user) {
    $request = new \Illuminate\Http\Request();
    $request->setUserResolver(fn() => $user);
    try {
        $controller = new App\Http\Controllers\Api\V1\DashboardController();
        $response = $controller->index($request);
        $content = $response->toResponse($request)->getContent();
        echo "✓ Dashboard API works - " . strlen($content) . " bytes\n";
    } catch (\Exception $e) {
        echo "✗ Dashboard API ERROR: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "⚠ Test user (ID 2) not found\n";
}
exit
EOF

# Test Customers endpoint
echo ""
echo "Testing Customers API..."
php artisan tinker <<'EOF'
$user = App\Models\User::find(2);
if ($user) {
    $request = new \Illuminate\Http\Request();
    $request->setUserResolver(fn() => $user);
    try {
        $controller = new App\Http\Controllers\Api\V1\CustomerController();
        $response = $controller->index($request);
        $content = $response->toResponse($request)->getContent();
        echo "✓ Customers API works - " . strlen($content) . " bytes\n";
    } catch (\Exception $e) {
        echo "✗ Customers API ERROR: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "⚠ Test user (ID 2) not found\n";
}
exit
EOF

echo ""
echo "===================================================="
echo "✓ Deployment Complete!"
echo "===================================================="
echo ""
echo "Next steps:"
echo "1. Test the mobile app (Hot Restart with 'R')"
echo "2. Check https://staging.kinvoice.ng in browser"
echo "3. Monitor logs: tail -f storage/logs/laravel.log"
echo ""
