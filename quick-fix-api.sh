#!/bin/bash

# Quick Fix for API 404 Errors
# Run this on staging to diagnose and fix API route issues

echo "========================================"
echo "Quick API Routes Fix"
echo "========================================"
echo ""

cd /var/www/staging.kinvoice.ng

echo "Step 1: Checking if routes/api.php exists..."
if [ -f "routes/api.php" ]; then
    echo "✓ routes/api.php exists ($(wc -l < routes/api.php) lines)"
else
    echo "✗ routes/api.php MISSING!"
    exit 1
fi
echo ""

echo "Step 2: Checking bootstrap/app.php for API routes..."
if grep -q "api: __DIR__" bootstrap/app.php; then
    echo "✓ API routes configured in bootstrap/app.php"
else
    echo "✗ API routes NOT configured!"
    echo "bootstrap/app.php needs to be updated"
    exit 1
fi
echo ""

echo "Step 3: Clearing ALL caches..."
php artisan optimize:clear
echo "✓ Caches cleared"
echo ""

echo "Step 4: Checking registered routes..."
ROUTE_COUNT=$(php artisan route:list --path=api/v1 --json 2>/dev/null | grep -c '"uri"')
echo "API v1 routes registered: $ROUTE_COUNT"

if [ "$ROUTE_COUNT" -eq "0" ]; then
    echo "✗ NO API ROUTES REGISTERED!"
    echo ""
    echo "Checking for errors..."
    php artisan route:list 2>&1 | head -20
    exit 1
else
    echo "✓ API routes are registered"
    echo ""
    echo "Sample routes:"
    php artisan route:list --path=api/v1 2>/dev/null | head -10
fi
echo ""

echo "Step 5: Caching routes for production..."
php artisan config:cache
php artisan route:cache
echo "✓ Configuration and routes cached"
echo ""

echo "Step 6: Testing route internally..."
php artisan tinker <<'PHPCODE'
try {
    $route = Route::getRoutes()->match(
        \Illuminate\Http\Request::create('/api/v1/auth/login', 'POST')
    );
    echo "✓ Route /api/v1/auth/login works internally\n";
} catch (\Exception $e) {
    echo "✗ Route error: " . $e->getMessage() . "\n";
}
exit
PHPCODE
echo ""

echo "Step 7: Restarting PHP-FPM..."
if systemctl is-active --quiet php8.3-fpm; then
    sudo systemctl restart php8.3-fpm
    echo "✓ PHP 8.3-FPM restarted"
elif systemctl is-active --quiet php8.2-fpm; then
    sudo systemctl restart php8.2-fpm
    echo "✓ PHP 8.2-FPM restarted"
fi
echo ""

echo "Step 8: Reloading web server..."
if command -v nginx &> /dev/null; then
    sudo systemctl reload nginx
    echo "✓ Nginx reloaded"
elif command -v apache2 &> /dev/null; then
    sudo systemctl reload apache2
    echo "✓ Apache reloaded"
fi
echo ""

echo "Step 9: Testing API endpoint..."
echo "Testing https://staging.kinvoice.ng/api/v1/auth/login"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" \
    -X POST https://staging.kinvoice.ng/api/v1/auth/login \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{}' 2>/dev/null)

echo "HTTP Status Code: $HTTP_CODE"

if [ "$HTTP_CODE" = "404" ]; then
    echo "✗ STILL GETTING 404!"
    echo ""
    echo "This suggests a web server configuration issue."
    echo "Checking nginx config..."
    echo ""
    if [ -f "/etc/nginx/sites-available/staging.kinvoice.ng" ]; then
        echo "Current nginx location blocks:"
        grep -A 3 "location " /etc/nginx/sites-available/staging.kinvoice.ng
    fi
elif [ "$HTTP_CODE" = "422" ] || [ "$HTTP_CODE" = "401" ]; then
    echo "✓ SUCCESS! API routes are working!"
    echo "Response:"
    curl -s -X POST https://staging.kinvoice.ng/api/v1/auth/login \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d '{}'
else
    echo "⚠ Unexpected status: $HTTP_CODE"
    echo "Response:"
    curl -s -X POST https://staging.kinvoice.ng/api/v1/auth/login \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d '{}'
fi
echo ""
echo ""

echo "========================================"
echo "Fix Complete"
echo "========================================"
