#!/bin/bash

# Test API Routes Registration on Staging

echo "===================================================="
echo "Testing API Routes on Staging"
echo "===================================================="
echo ""

cd /var/www/staging.kinvoice.ng

echo "1. Checking bootstrap/app.php configuration..."
echo "----------------------------------------------------"
if grep -q "api: __DIR__.'/../routes/api.php'" bootstrap/app.php; then
    echo "✓ API routes are configured in bootstrap/app.php"
else
    echo "✗ API routes NOT configured in bootstrap/app.php"
    echo "This is the problem! The bootstrap/app.php needs to be updated."
    exit 1
fi
echo ""

echo "2. Clearing all caches..."
echo "----------------------------------------------------"
php artisan optimize:clear
echo "✓ All caches cleared"
echo ""

echo "3. Checking if routes/api.php exists..."
echo "----------------------------------------------------"
if [ -f "routes/api.php" ]; then
    echo "✓ routes/api.php exists"
    echo "File size: $(wc -l < routes/api.php) lines"
else
    echo "✗ routes/api.php NOT FOUND!"
    exit 1
fi
echo ""

echo "4. Listing all registered routes..."
echo "----------------------------------------------------"
echo "Total routes:"
php artisan route:list --json | grep -c '"uri"'
echo ""
echo "API routes with /api prefix:"
php artisan route:list --path=api | wc -l
echo ""
echo "Sample API routes:"
php artisan route:list --path=api/v1 | head -15
echo ""

echo "5. Testing route resolution programmatically..."
echo "----------------------------------------------------"
php artisan tinker <<'EOF'
use Illuminate\Support\Facades\Route;

// Test if the /api/v1/auth/login route exists
try {
    $route = Route::getRoutes()->match(
        \Illuminate\Http\Request::create('/api/v1/auth/login', 'POST')
    );
    echo "✓ Route /api/v1/auth/login is registered\n";
    echo "  Controller: " . ($route->getActionName() ?? 'N/A') . "\n";
} catch (\Exception $e) {
    echo "✗ Route /api/v1/auth/login NOT FOUND\n";
    echo "  Error: " . $e->getMessage() . "\n";
}

// Test dashboard route
try {
    $route = Route::getRoutes()->match(
        \Illuminate\Http\Request::create('/api/v1/dashboard', 'GET')
    );
    echo "✓ Route /api/v1/dashboard is registered\n";
} catch (\Exception $e) {
    echo "✗ Route /api/v1/dashboard NOT FOUND\n";
}

exit
EOF
echo ""

echo "6. Testing HTTP requests..."
echo "----------------------------------------------------"
echo "Testing /api/v1/auth/login (should return 422 or 401, NOT 404):"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" \
    -X POST https://staging.kinvoice.ng/api/v1/auth/login \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{}')
echo "HTTP Status: $HTTP_CODE"

if [ "$HTTP_CODE" = "404" ]; then
    echo "✗ 404 Error - Routes are not accessible via web server"
    echo ""
    echo "This means the web server CAN reach Laravel, but the routes"
    echo "are not registered. Possible causes:"
    echo "  1. Route cache is stale"
    echo "  2. API routes not loaded in bootstrap/app.php"
    echo "  3. Need to run: php artisan optimize:clear && php artisan config:cache"
elif [ "$HTTP_CODE" = "422" ] || [ "$HTTP_CODE" = "401" ]; then
    echo "✓ Route is accessible! (Validation error is expected)"
else
    echo "⚠ Unexpected status code: $HTTP_CODE"
fi
echo ""

echo "Full response:"
curl -s -X POST https://staging.kinvoice.ng/api/v1/auth/login \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{"email":"test@test.com","password":"test"}'
echo ""
echo ""

echo "===================================================="
echo "Test Complete"
echo "===================================================="
echo ""
echo "If routes show as registered in step 4 but return 404 in step 6,"
echo "run these commands:"
echo ""
echo "  php artisan config:cache"
echo "  php artisan route:cache"
echo "  sudo systemctl restart php8.3-fpm"
echo "  sudo systemctl reload nginx"
echo ""
