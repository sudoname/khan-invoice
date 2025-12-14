#!/bin/bash

# Fix Staging Server Issues
# This script fixes Google Sign-In and enables API access for admin user

echo "==================================================="
echo "Fixing Khan Invoice Staging Server"
echo "==================================================="
echo ""

# Navigate to project directory
cd /var/www/staging.kinvoice.ng

echo "1. Pulling latest code from repository..."
git pull origin main

echo ""
echo "2. Installing dependencies (including Google API PHP Client)..."
composer install --no-dev --optimize-autoloader --no-interaction

echo ""
echo "3. Checking .env configuration..."

# Check if GOOGLE_CLIENT_ID exists in .env
if grep -q "GOOGLE_CLIENT_ID" .env; then
    echo "   ✓ GOOGLE_CLIENT_ID already exists in .env"
else
    echo "   ✗ GOOGLE_CLIENT_ID not found in .env"
    echo "   Adding GOOGLE_CLIENT_ID to .env..."
    # Add the Google Client ID from the kinvoice-479612 project
    echo "" >> .env
    echo "# Google OAuth Configuration" >> .env
    echo "GOOGLE_CLIENT_ID=97623786528-ssbtuo5j1pdhd37vb2l6e0i8l5ra67j9.apps.googleusercontent.com" >> .env
    echo "   ✓ GOOGLE_CLIENT_ID added"
fi

echo ""
echo "4. Clearing Laravel caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan route:cache

echo ""
echo "5. Enabling API access for ALL verified users..."
php artisan users:enable-api-for-verified

echo ""
echo "6. Restarting PHP-FPM..."
# Try different PHP versions
if systemctl is-active --quiet php8.3-fpm; then
    sudo systemctl restart php8.3-fpm
    echo "   ✓ PHP 8.3 FPM restarted"
elif systemctl is-active --quiet php8.2-fpm; then
    sudo systemctl restart php8.2-fpm
    echo "   ✓ PHP 8.2 FPM restarted"
elif systemctl is-active --quiet php8.1-fpm; then
    sudo systemctl restart php8.1-fpm
    echo "   ✓ PHP 8.1 FPM restarted"
else
    echo "   ! Could not find PHP-FPM service"
fi

echo ""
echo "==================================================="
echo "Testing API endpoints..."
echo "==================================================="
echo ""

echo "Test 1: Google Login Endpoint"
curl -X POST http://localhost/api/v1/auth/google \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"id_token":"test"}' 2>/dev/null | python3 -m json.tool || echo "   (Expected to fail with invalid token)"

echo ""
echo ""
echo "Test 2: Verify verified users now have API access"
php artisan tinker --execute="echo 'Verified users with API enabled: ' . App\Models\User::whereNotNull('email_verified_at')->where('api_enabled', true)->count();"

echo ""
echo ""
echo "==================================================="
echo "Setup Complete!"
echo "==================================================="
echo ""
echo "What was fixed:"
echo "1. ✓ Pulled latest code with auto-API-enable feature"
echo "2. ✓ Installed Google API PHP Client"
echo "3. ✓ Added GOOGLE_CLIENT_ID to .env"
echo "4. ✓ Enabled API access for ALL verified users"
echo "5. ✓ From now on, users automatically get API access when they verify email"
echo ""
echo "Next steps:"
echo "1. Test Google Sign-In from the mobile app"
echo "2. Test login with any verified email (API should now be enabled)"
echo "3. If Google Sign-In still fails, check Laravel logs:"
echo "   tail -f storage/logs/laravel.log"
echo ""
