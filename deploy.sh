#!/bin/bash

# Khan Invoice - Deployment Script
# Run this script after cloning the repository

set -e

echo "========================================="
echo "Khan Invoice - Application Deployment"
echo "========================================="
echo ""

cd /var/www/kinvoice.ng

# Install Composer dependencies
echo "Step 1: Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader

# Install Node dependencies
echo ""
echo "Step 2: Installing Node.js dependencies..."
npm install

# Copy environment file
echo ""
echo "Step 3: Setting up environment file..."
if [ ! -f .env ]; then
    cp .env.example .env
    echo "✓ .env file created. Please update with production values!"
fi

# Generate application key
echo ""
echo "Step 4: Generating application key..."
php artisan key:generate

# Create storage symlink
echo ""
echo "Step 5: Creating storage symlink..."
php artisan storage:link

# Build frontend assets
echo ""
echo "Step 6: Building frontend assets..."
npm run build

# Run database migrations
echo ""
echo "Step 7: Running database migrations..."
php artisan migrate --force

# Optimize Laravel
echo ""
echo "Step 8: Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
echo ""
echo "Step 9: Setting file permissions..."
chown -R www-data:www-data /var/www/kinvoice.ng
chmod -R 755 /var/www/kinvoice.ng
chmod -R 775 /var/www/kinvoice.ng/storage
chmod -R 775 /var/www/kinvoice.ng/bootstrap/cache

# Restart services
echo ""
echo "Step 10: Restarting services..."
systemctl restart php8.3-fpm
systemctl restart nginx

echo ""
echo "========================================="
echo "Deployment completed successfully!"
echo "========================================="
echo ""
echo "Next steps:"
echo "1. Update .env file with production values"
echo "2. Create admin user: php artisan make:admin"
echo "3. Configure SSL: certbot --nginx -d kinvoice.ng -d www.kinvoice.ng"
echo "========================================="
