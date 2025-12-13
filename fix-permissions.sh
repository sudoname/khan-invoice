#!/bin/bash

# Khan Invoice - Fix File Permissions
# Fixes permission issues after deployment

set -e

echo "========================================="
echo "Khan Invoice - Fix Permissions"
echo "========================================="
echo ""

# Change to staging directory
cd /var/www/staging.kinvoice.ng

echo "Step 1: Setting directory ownership to www-data..."
sudo chown -R www-data:www-data .

echo ""
echo "Step 2: Setting base directory permissions..."
sudo chmod -R 755 .

echo ""
echo "Step 3: Setting storage directory permissions..."
sudo chmod -R 775 storage
sudo chmod -R 775 storage/framework
sudo chmod -R 775 storage/framework/cache
sudo chmod -R 775 storage/framework/sessions
sudo chmod -R 775 storage/framework/views
sudo chmod -R 775 storage/logs

echo ""
echo "Step 4: Setting bootstrap/cache permissions..."
sudo chmod -R 775 bootstrap/cache

echo ""
echo "Step 5: Clearing Laravel caches..."
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan view:clear
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan route:clear

echo ""
echo "Step 6: Re-caching optimizations..."
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

echo ""
echo "========================================="
echo "✅ Permissions Fixed Successfully!"
echo "========================================="
echo ""
echo "Directory Permissions:"
echo "  • Base directory: 755 (www-data:www-data)"
echo "  • storage/: 775 (www-data:www-data)"
echo "  • bootstrap/cache/: 775 (www-data:www-data)"
echo ""
echo "Caches cleared and rebuilt."
echo ""
echo "You can now access the application at:"
echo "  https://staging.kinvoice.ng"
echo ""
echo "========================================="
