#!/bin/bash

# Deployment script for staging.kinvoice.ng
# Run: bash deploy_to_staging.sh

echo "================================================"
echo "  Deploying Subscription Upgrade/Downgrade"
echo "  to staging.kinvoice.ng"
echo "================================================"
echo ""

# Step 1: Commit local changes
echo "Step 1: Committing local changes..."
git add app/Filament/App/Pages/MySubscription.php
git add app/Filament/App/Pages/SubscriptionPlans.php
git add app/Http/Controllers/SubscriptionUpgradeController.php
git add resources/views/filament/app/pages/my-subscription.blade.php
git add resources/views/filament/app/pages/subscription-plans.blade.php
git add routes/web.php

git commit -m "feat: complete subscription upgrade/downgrade with credits

- Enhanced SubscriptionUpgradeController with full upgrade/downgrade logic
- Updated SubscriptionPlans page with credit display and AJAX integration
- Updated MySubscription page with credit balance and history
- Added subscription routes for upgrade/downgrade/verify
- Implemented instant email notifications (not queued)
- Full UI with credit displays and Paystack integration

Features:
- Prorated billing calculations
- Account credit system with FIFO
- 30-day restriction for annual plans
- Automatic credit application
- Credit expiration tracking
- Full audit trail"

if [ $? -eq 0 ]; then
    echo "✓ Changes committed successfully"
else
    echo "✗ Commit failed or nothing to commit"
fi

echo ""

# Step 2: Push to remote
echo "Step 2: Pushing to remote repository..."
git push origin main

if [ $? -eq 0 ]; then
    echo "✓ Pushed to remote successfully"
else
    echo "✗ Push failed"
    exit 1
fi

echo ""
echo "================================================"
echo "  Local Push Complete!"
echo "================================================"
echo ""
echo "NEXT STEPS ON STAGING SERVER:"
echo "------------------------------"
echo "1. SSH into staging:"
echo "   ssh user@staging.kinvoice.ng"
echo ""
echo "2. Pull changes:"
echo "   cd /path/to/khan-invoice"
echo "   git pull origin main"
echo ""
echo "3. Run migration:"
echo "   php artisan migrate"
echo ""
echo "4. Clear caches:"
echo "   php artisan optimize:clear"
echo ""
echo "5. Verify routes:"
echo "   php artisan route:list --name=subscription"
echo ""
echo "6. Test in browser:"
echo "   https://staging.kinvoice.ng/app/subscription-plans"
echo ""
echo "See STAGING_DEPLOYMENT_CHECKLIST.md for full details"
echo ""
