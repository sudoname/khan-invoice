#!/bin/bash

# Diagnose Staging Server Issues
# Run this on the staging server to identify problems

echo "===================================================="
echo "Staging Server Diagnostics"
echo "===================================================="
echo ""

cd /var/www/staging.kinvoice.ng

echo "1. Checking Laravel Installation..."
echo "----------------------------------------------------"
if [ -f "artisan" ]; then
    echo "✓ Laravel installation found"
else
    echo "✗ Laravel artisan file not found!"
    exit 1
fi
echo ""

echo "2. Checking Routes..."
echo "----------------------------------------------------"
php artisan route:clear
php artisan route:cache
echo "Route count:"
php artisan route:list --json | grep -o '"uri"' | wc -l
echo ""
echo "API v1 routes:"
php artisan route:list --path=api/v1 | head -10
echo ""

echo "3. Checking .env Configuration..."
echo "----------------------------------------------------"
if [ -f ".env" ]; then
    echo "✓ .env file exists"
    echo "APP_URL=$(grep APP_URL .env)"
    echo "APP_ENV=$(grep APP_ENV .env)"
    echo "APP_DEBUG=$(grep APP_DEBUG .env)"
else
    echo "✗ .env file not found!"
fi
echo ""

echo "4. Checking Directory Permissions..."
echo "----------------------------------------------------"
echo "Storage directory:"
ls -ld storage
echo "Bootstrap cache:"
ls -ld bootstrap/cache
echo ""

echo "5. Checking Web Server Configuration..."
echo "----------------------------------------------------"
if [ -f "/etc/nginx/sites-available/staging.kinvoice.ng" ]; then
    echo "Nginx config found:"
    grep -A 5 "root " /etc/nginx/sites-available/staging.kinvoice.ng | head -6
elif [ -f "/etc/apache2/sites-available/staging.kinvoice.ng.conf" ]; then
    echo "Apache config found:"
    grep -A 5 "DocumentRoot" /etc/apache2/sites-available/staging.kinvoice.ng.conf | head -6
else
    echo "⚠ Web server config not found in standard locations"
fi
echo ""

echo "6. Testing Internal Routes..."
echo "----------------------------------------------------"
echo "Testing /api/v1/auth/login route internally:"
php artisan tinker <<'EOF'
$response = Route::getRoutes()->match(
    Illuminate\Http\Request::create('/api/v1/auth/login', 'POST')
);
echo "✓ Route found: " . get_class($response->getAction()['controller'] ?? 'N/A') . "\n";
exit
EOF
echo ""

echo "7. Checking Public Directory..."
echo "----------------------------------------------------"
if [ -f "public/index.php" ]; then
    echo "✓ public/index.php exists"
    ls -l public/index.php
else
    echo "✗ public/index.php not found!"
fi
echo ""

echo "8. Testing Direct PHP Execution..."
echo "----------------------------------------------------"
echo "Creating test file..."
echo "<?php echo 'PHP Works! Laravel Path: ' . realpath(__DIR__.'/../bootstrap/app.php');" > public/test-php.php
curl -s http://localhost/test-php.php || echo "✗ Cannot access via localhost"
rm -f public/test-php.php
echo ""

echo "9. Checking PHP-FPM Status..."
echo "----------------------------------------------------"
if systemctl is-active --quiet php8.3-fpm; then
    echo "✓ PHP 8.3 FPM is running"
    systemctl status php8.3-fpm | grep "Active:"
elif systemctl is-active --quiet php8.2-fpm; then
    echo "✓ PHP 8.2 FPM is running"
    systemctl status php8.2-fpm | grep "Active:"
else
    echo "⚠ PHP-FPM status unknown"
fi
echo ""

echo "10. Checking Recent Error Logs..."
echo "----------------------------------------------------"
if [ -f "storage/logs/laravel.log" ]; then
    echo "Recent Laravel errors:"
    tail -20 storage/logs/laravel.log | grep ERROR || echo "No recent errors"
else
    echo "⚠ No Laravel log file found"
fi
echo ""

echo "===================================================="
echo "Diagnosis Complete"
echo "===================================================="
echo ""
echo "Next Steps:"
echo "1. If routes are missing, run: php artisan route:cache"
echo "2. If permissions are wrong, run: sudo chown -R www-data:www-data storage bootstrap/cache"
echo "3. If web server config is wrong, check the root directive points to /var/www/staging.kinvoice.ng/public"
echo "4. Check nginx/apache error logs for more details"
echo ""
