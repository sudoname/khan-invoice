#!/bin/bash

# Khan Invoice - Staging Deployment Script
# Deploys latest changes to staging.kinvoice.com

set -e

echo "========================================="
echo "Khan Invoice - Staging Deployment"
echo "========================================="
echo ""

# Change to staging directory
cd /var/www/staging.kinvoice.com

echo "Step 1: Pulling latest changes from GitHub..."
git pull origin main

echo ""
echo "Step 2: Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader

echo ""
echo "Step 3: Installing Node.js dependencies..."
npm install

echo ""
echo "Step 4: Building frontend assets..."
npm run build

echo ""
echo "Step 5: Running database migrations..."
php artisan migrate --force

echo ""
echo "Step 6: Seeding global currencies..."
php artisan db:seed --class=GlobalCurrenciesSeeder --force

echo ""
echo "Step 7: Clearing and optimizing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

echo ""
echo "Step 8: Setting file permissions..."
chown -R www-data:www-data /var/www/staging.kinvoice.com
chmod -R 755 /var/www/staging.kinvoice.com
chmod -R 775 /var/www/staging.kinvoice.com/storage
chmod -R 775 /var/www/staging.kinvoice.com/bootstrap/cache

echo ""
echo "Step 9: Restarting PHP-FPM and Queue Worker..."
systemctl restart php8.3-fpm

# Restart queue worker if exists
if systemctl is-active --quiet laravel-worker; then
    systemctl restart laravel-worker
    echo "✓ Queue worker restarted"
else
    echo "⚠ Queue worker not configured (optional)"
fi

# Restart scheduler if exists
if systemctl is-active --quiet laravel-scheduler; then
    systemctl restart laravel-scheduler
    echo "✓ Scheduler restarted"
else
    echo "⚠ Scheduler not configured (optional)"
fi

echo ""
echo "========================================="
echo "🎉 Staging Deployment Completed!"
echo "========================================="
echo ""
echo "Deployed Features:"
echo "  ✓ Multi-Currency (50 currencies)"
echo "  ✓ SMS Notifications (Termii)"
echo "  ✓ WhatsApp Notifications (Twilio)"
echo "  ✓ Automatic Payment Reminders"
echo "  ✓ REST API with Sanctum Auth"
echo "  ✓ Enhanced Reports"
echo ""
echo "URLs:"
echo "  • Staging: https://staging.kinvoice.com"
echo "  • Admin: https://staging.kinvoice.com/app"
echo "  • API: https://staging.kinvoice.com/api/v1"
echo ""
echo "Next Steps:"
echo "  1. Test multi-currency invoice creation"
echo "  2. Configure Termii API keys in .env"
echo "  3. Configure Twilio API keys in .env"
echo "  4. Test API endpoints"
echo "  5. Set up cron job for scheduler"
echo ""
echo "========================================="
