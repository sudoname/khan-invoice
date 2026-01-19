#!/bin/bash

echo "========================================="
echo "Deploying Contact Reply Feature"
echo "========================================="
echo ""

# Pull latest changes
echo "Pulling latest changes..."
git pull origin feature/payment-orchestration-v2

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Clear caches
echo "Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear

echo ""
echo "✓ Deployment complete!"
echo ""
echo "Contact message reply feature is now live."
echo "Admins can reply to messages at: https://kinvoice.ng/admin/contact-messages"
