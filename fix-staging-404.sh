#!/bin/bash

# Fix 404 Errors on Staging
# Common fixes for Laravel API routes returning 404

set -e

echo "===================================================="
echo "Fix Staging 404 Errors"
echo "===================================================="
echo ""

cd /var/www/staging.kinvoice.ng

echo "1. Clearing all caches..."
echo "----------------------------------------------------"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo "✓ Caches cleared"
echo ""

echo "2. Re-caching routes..."
echo "----------------------------------------------------"
php artisan route:cache
echo "✓ Routes cached"
echo ""

echo "3. Verifying route registration..."
echo "----------------------------------------------------"
ROUTE_COUNT=$(php artisan route:list --json | grep -o '"uri"' | wc -l)
echo "Total routes registered: $ROUTE_COUNT"
php artisan route:list --path=api/v1 | head -5
echo ""

echo "4. Fixing file permissions..."
echo "----------------------------------------------------"
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
echo "✓ Permissions fixed"
echo ""

echo "5. Checking web server configuration..."
echo "----------------------------------------------------"

# Check if nginx is being used
if [ -f "/etc/nginx/sites-available/staging.kinvoice.ng" ]; then
    echo "Nginx detected. Checking configuration..."

    # Check if root points to public directory
    if grep -q "root.*staging.kinvoice.ng/public" /etc/nginx/sites-available/staging.kinvoice.ng; then
        echo "✓ Nginx root correctly points to public directory"
    else
        echo "⚠ WARNING: Nginx root may not point to public directory"
        echo "Current root directive:"
        grep "root " /etc/nginx/sites-available/staging.kinvoice.ng
        echo ""
        echo "It should be: root /var/www/staging.kinvoice.ng/public;"
        echo ""
        read -p "Would you like me to show the correct nginx config? (y/n) " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            cat << 'NGINXCONF'

Correct Nginx Configuration:
----------------------------
server {
    listen 80;
    server_name staging.kinvoice.ng;
    root /var/www/staging.kinvoice.ng/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}

Save this to /etc/nginx/sites-available/staging.kinvoice.ng
Then run: sudo nginx -t && sudo systemctl reload nginx
NGINXCONF
        fi
    fi

    # Test nginx config
    sudo nginx -t

    # Reload nginx
    echo "Reloading nginx..."
    sudo systemctl reload nginx
    echo "✓ Nginx reloaded"

# Check if Apache is being used
elif [ -f "/etc/apache2/sites-available/staging.kinvoice.ng.conf" ]; then
    echo "Apache detected. Checking configuration..."

    if grep -q "DocumentRoot.*staging.kinvoice.ng/public" /etc/apache2/sites-available/staging.kinvoice.ng.conf; then
        echo "✓ Apache DocumentRoot correctly points to public directory"
    else
        echo "⚠ WARNING: Apache DocumentRoot may not point to public directory"
        echo "It should be: DocumentRoot /var/www/staging.kinvoice.ng/public"
    fi

    # Ensure mod_rewrite is enabled
    sudo a2enmod rewrite
    sudo systemctl reload apache2
    echo "✓ Apache reloaded"
else
    echo "⚠ Could not detect web server configuration"
fi
echo ""

echo "6. Restarting PHP-FPM..."
echo "----------------------------------------------------"
if systemctl is-active --quiet php8.3-fpm; then
    sudo systemctl restart php8.3-fpm
    echo "✓ PHP 8.3 FPM restarted"
elif systemctl is-active --quiet php8.2-fpm; then
    sudo systemctl restart php8.2-fpm
    echo "✓ PHP 8.2 FPM restarted"
fi
echo ""

echo "7. Testing API endpoint..."
echo "----------------------------------------------------"
echo "Testing login endpoint (should return 422 validation error, not 404):"
curl -s -o /dev/null -w "HTTP Status: %{http_code}\n" \
    -X POST https://staging.kinvoice.ng/api/v1/auth/login \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
echo ""

echo "Testing with actual request:"
curl -X POST https://staging.kinvoice.ng/api/v1/auth/login \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{"email":"test","password":"test"}' | head -c 200
echo ""
echo ""

echo "===================================================="
echo "Fix Complete!"
echo "===================================================="
echo ""
echo "If you still see 404 errors, the web server root"
echo "directory likely needs to be updated to point to:"
echo "/var/www/staging.kinvoice.ng/public"
echo ""
echo "Check the web server error logs:"
echo "  Nginx: sudo tail -f /var/log/nginx/error.log"
echo "  Apache: sudo tail -f /var/log/apache2/error.log"
echo ""
