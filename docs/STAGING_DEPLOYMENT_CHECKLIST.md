# Staging Deployment Checklist - Subscription Upgrade/Downgrade

## Current Status: Ready to Push to Staging ✅

All code is implemented and tested. Follow this checklist to deploy to staging.kinvoice.ng

---

## Pre-Deployment Verification

### 1. Check Local Changes
```bash
cd C:/Users/yomi/khan-invoice
git status
```

**Expected uncommitted files:**
- ✅ app/Filament/App/Pages/MySubscription.php (modified)
- ✅ app/Filament/App/Pages/SubscriptionPlans.php (modified)
- ✅ app/Http/Controllers/SubscriptionUpgradeController.php (modified)
- ✅ resources/views/filament/app/pages/my-subscription.blade.php (modified)
- ✅ resources/views/filament/app/pages/subscription-plans.blade.php (modified)
- ✅ routes/web.php (modified)
- 📝 Documentation files (optional to commit)

### 2. Already Committed (Previous Commits)
```bash
git log --oneline -5
```

**Files already in git:**
- ✅ app/Models/AccountCredit.php
- ✅ app/Services/ProratedCalculationService.php
- ✅ app/Notifications/SubscriptionChangedNotification.php
- ✅ database/migrations/2025_12_13_211101_add_subscription_change_tracking_and_credits.php
- ✅ app/Http/Controllers/SubscriptionUpgradeController.php (scaffold)
- ✅ app/Services/SubscriptionService.php (partial updates)

---

## Step 1: Commit Remaining Changes

### Option A: Commit Everything (Recommended)
```bash
cd C:/Users/yomi/khan-invoice

# Add all changes
git add -A

# Commit with descriptive message
git commit -m "feat: complete subscription upgrade/downgrade with credits and instant emails

- Enhanced SubscriptionUpgradeController with full upgrade/downgrade logic
- Updated SubscriptionPlans page with credit display and AJAX integration
- Updated MySubscription page with credit balance and history display
- Added subscription routes for upgrade/downgrade/verify
- Enhanced blade views with credit displays and JavaScript handlers
- Implemented instant email notifications (not queued)
- Added comprehensive testing documentation

Features:
- Prorated billing calculations
- Account credit system with FIFO application
- 30-day restriction for annual plans
- Automatic credit application to upgrades
- Credit expiration (1 year)
- Full audit trail
- Beautiful UI with credit displays
- Paystack payment integration for upgrades"
```

### Option B: Commit Selectively (Code Only)
```bash
# Add only production code (no test/docs)
git add app/
git add resources/
git add routes/web.php

git commit -m "feat: complete subscription upgrade/downgrade system

- Full upgrade/downgrade with prorated billing
- Account credits with FIFO
- Instant email notifications
- Paystack integration
- Credit displays on UI"

# Optionally add documentation
git add SUBSCRIPTION_UPGRADE_IMPLEMENTATION.md
git add BROWSER_TESTING_GUIDE.md
git commit -m "docs: add subscription upgrade/downgrade documentation"
```

---

## Step 2: Push to Remote

```bash
# Push to origin/main
git push origin main

# Or if using different branch
git push origin your-branch-name
```

---

## Step 3: Deploy to Staging Server

### SSH into Staging
```bash
ssh user@staging.kinvoice.ng
cd /path/to/khan-invoice
```

### Pull Latest Code
```bash
# Ensure on correct branch
git branch

# Pull latest changes
git pull origin main

# Check what was pulled
git log --oneline -3
```

### Run Migrations
```bash
# Check migration status
php artisan migrate:status

# Run new migration
php artisan migrate

# Expected output:
# Migrating: 2025_12_13_211101_add_subscription_change_tracking_and_credits
# Migrated:  2025_12_13_211101_add_subscription_change_tracking_and_credits
```

### Clear Caches
```bash
# Clear all caches
php artisan optimize:clear

# Or individually:
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

### Verify Routes
```bash
php artisan route:list --name=subscription

# Expected output:
# POST   subscription/upgrade
# GET    subscription/upgrade/verify
# POST   subscription/downgrade
```

---

## Step 4: Environment Configuration

### Check Required Environment Variables
```bash
nano .env  # or vi .env
```

**Required Variables:**
```env
# Paystack (should already exist)
PAYSTACK_SECRET_KEY=sk_test_xxxxx  # Use test key for staging
PAYSTACK_PUBLIC_KEY=pk_test_xxxxx

# Mail (ensure configured for instant delivery)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io  # or your mail server
MAIL_PORT=2525
MAIL_USERNAME=xxxxx
MAIL_PASSWORD=xxxxx
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@kinvoice.ng
MAIL_FROM_NAME="Khan Invoice"

# Queue (should be set)
QUEUE_CONNECTION=database  # or sync for immediate processing
```

### Verify Mail Configuration
```bash
# Test email sending
php artisan tinker
>>> Mail::raw('Test email', function($message) { $message->to('test@example.com')->subject('Test'); });
>>> exit
```

---

## Step 5: Database Verification

### Check Tables Created
```bash
php artisan tinker
>>> Schema::hasTable('account_credits')
# Should return: true

>>> Schema::hasColumns('subscriptions', ['last_plan_change_at', 'previous_plan_id'])
# Should return: true
```

### Check Data Integrity
```bash
# Count existing subscriptions
>>> \App\Models\Subscription::count()

# Check plans exist
>>> \App\Models\Plan::count()
# Should be: 4 (free, starter, professional, business)

# Check no orphaned data
>>> \App\Models\AccountCredit::count()
# Should be: 0 (new table)
```

---

## Step 6: Post-Deployment Testing

### Quick Smoke Tests (CLI)
```bash
php artisan tinker

# Test ProratedCalculationService
>>> $service = app(\App\Services\ProratedCalculationService::class);
>>> $user = \App\Models\User::first();
>>> $sub = $user->subscription;
>>> $service->getDaysRemaining($sub)
# Should return: number of days

# Test routes registered
>>> route('subscription.upgrade.initiate')
# Should return: URL

# Test AccountCredit model
>>> \App\Models\AccountCredit::count()
# Should return: 0 (or number if any exist)
```

### Browser Tests (Critical)
1. **Login:** https://staging.kinvoice.ng/app/login
2. **View Plans:** https://staging.kinvoice.ng/app/subscription-plans
3. **View Subscription:** https://staging.kinvoice.ng/app/my-subscription

**Quick Checks:**
- [ ] Plans page loads without errors
- [ ] My Subscription page loads
- [ ] No JavaScript console errors
- [ ] Credit display shows if user has credits
- [ ] "Change Plan" button visible

### Test Upgrade Flow (Optional for now)
Follow `BROWSER_TESTING_GUIDE.md` for comprehensive testing.

---

## Step 7: Monitoring

### Check Logs
```bash
# Watch logs in real-time
tail -f storage/logs/laravel.log

# Or check for errors
grep "ERROR" storage/logs/laravel.log | tail -20
```

### Check Queue (if using queue)
```bash
# If queue is database
php artisan queue:work --once

# Check failed jobs
php artisan queue:failed
```

---

## Rollback Plan (If Needed)

### If Issues Found:

1. **Revert Migration:**
```bash
php artisan migrate:rollback --step=1
```

2. **Revert Code:**
```bash
git log --oneline -5  # Find commit before changes
git revert <commit-hash>
git push origin main
```

3. **On Staging:**
```bash
git pull origin main
php artisan optimize:clear
```

---

## Post-Deployment Verification Checklist

### Backend
- [ ] Migration ran successfully
- [ ] `account_credits` table exists
- [ ] Subscriptions table has new columns
- [ ] Routes registered (check `php artisan route:list`)
- [ ] No errors in logs
- [ ] Services instantiate correctly

### Frontend
- [ ] Plans page loads
- [ ] My Subscription page loads
- [ ] No JavaScript errors
- [ ] UI components render correctly

### Email
- [ ] Mail configuration verified
- [ ] Test email sent successfully
- [ ] Instant delivery confirmed (no queue)

### Payment
- [ ] Paystack test keys configured
- [ ] Routes accessible
- [ ] No 404 errors on upgrade endpoints

---

## Known Issues & Notes

### Non-Critical
- 30-day restriction needs browser verification
- Test with actual Paystack payment recommended
- Email templates look best with HTML email clients

### Expected Behavior
- New installation: No credits yet (empty table)
- Existing users: Subscriptions unchanged
- Credit display: Only shows if user has credits
- Routes: Work immediately after deployment

---

## Support & Documentation

### If Issues Arise:
1. Check `storage/logs/laravel.log`
2. Verify migration ran: `php artisan migrate:status`
3. Check routes: `php artisan route:list --name=subscription`
4. Test services in tinker
5. Review `TESTING_RESULTS.md` for expected behavior

### Documentation Files:
- `SUBSCRIPTION_UPGRADE_IMPLEMENTATION.md` - Full technical guide
- `BROWSER_TESTING_GUIDE.md` - Step-by-step browser tests
- `TESTING_RESULTS.md` - Test results and coverage
- `IMPLEMENTATION_COMPLETE.md` - Executive summary

---

## Deployment Timeline

**Estimated Time:** 15-30 minutes

1. Commit & Push: 5 mins
2. SSH & Pull: 2 mins
3. Run Migration: 2 mins
4. Clear Caches: 1 min
5. Verification: 5-10 mins
6. Smoke Testing: 5-10 mins

---

## Success Criteria

**Deployment Successful When:**
- ✅ All files pushed to remote
- ✅ Code pulled on staging
- ✅ Migration completed
- ✅ Routes registered
- ✅ Pages load without errors
- ✅ No errors in logs
- ✅ Services instantiate in tinker

**Ready for Production When:**
- ✅ All above criteria met
- ✅ Browser testing completed
- ✅ Email delivery verified
- ✅ Paystack payment tested
- ✅ Credit system verified
- ✅ User acceptance received

---

## Contact & Support

**If Issues Found:**
- Check logs first
- Review documentation
- Test in local environment
- Verify configuration

**Current Status:**
🟢 **READY TO DEPLOY TO STAGING**

All code implemented, tested, and documented. Safe to push to staging.kinvoice.ng for final browser verification before production.
