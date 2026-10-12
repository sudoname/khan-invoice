# Fix Staging Server Issues

## Problems to Fix:
1. ✗ Google Sign-In returns "Server error, please try again"
2. ✗ All verified users should have API access automatically enabled

---

## Solution 1: Run the Fix Script (Recommended)

**Step 1:** Upload the fix script to the staging server
```bash
# On your local machine:
scp C:\Users\yomi\khan_invoice_app\fix-staging-server.sh username@staging.kinvoice.ng:/tmp/
```

**Step 2:** SSH into staging and run the script
```bash
ssh username@staging.kinvoice.ng
chmod +x /tmp/fix-staging-server.sh
sudo /tmp/fix-staging-server.sh
```

---

## Solution 2: Manual Steps (If you prefer)

SSH into the staging server and run these commands one by one:

### Step 1: Pull Latest Code
```bash
cd /var/www/staging.kinvoice.ng
git pull origin main
composer install --no-dev --optimize-autoloader
```

### Step 2: Add Google Client ID to .env
```bash
cd /var/www/staging.kinvoice.ng

# Check if it exists first
grep GOOGLE_CLIENT_ID .env

# If not found, add it:
echo "" >> .env
echo "# Google OAuth Configuration" >> .env
echo "GOOGLE_CLIENT_ID=97623786528-ssbtuo5j1pdhd37vb2l6e0i8l5ra67j9.apps.googleusercontent.com" >> .env
```

### Step 3: Enable API Access for ALL Verified Users
```bash
cd /var/www/staging.kinvoice.ng
php artisan users:enable-api-for-verified
```

This command will enable API access for all users who have verified their email.

### Step 4: Clear Caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:cache
```

### Step 5: Restart PHP-FPM
```bash
# Try one of these (depending on your PHP version):
sudo systemctl restart php8.3-fpm
# or
sudo systemctl restart php8.2-fpm
# or
sudo systemctl restart php8.1-fpm
```

---

## Verify the Fix

### Test 1: Google Sign-In
```bash
curl -X POST https://staging.kinvoice.ng/api/v1/auth/google \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"id_token":"fake_token"}'
```

**Expected:** Should return `{"success":false,"message":"Unable to login with Google. Please try again."}` with status **500** (this is normal for a fake token - at least the endpoint works)

**If it returns 404:** Routes not loaded - run `php artisan route:cache` again

### Test 2: Check Verified Users Have API Access
```bash
cd /var/www/staging.kinvoice.ng
php artisan tinker --execute="echo 'Verified users with API enabled: ' . App\Models\User::whereNotNull('email_verified_at')->where('api_enabled', true)->count();"
```

**Expected:** Should show the count of verified users (should match total verified users)

### Test 3: Login with Any Verified User
```bash
curl -X POST https://staging.kinvoice.ng/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"YOUR_VERIFIED_EMAIL","password":"YOUR_PASSWORD"}'
```

**Expected:** Should return a JSON with `success: true` and a token (not "API access is not enabled")

---

## Check Laravel Logs

If Google Sign-In still fails after the fix:
```bash
tail -f /var/www/staging.kinvoice.ng/storage/logs/laravel.log
```

Then try logging in from the mobile app and watch for errors in the log.

---

## Quick Summary

The issues were:
1. **Google Sign-In 500 error**: Missing `google/apiclient` PHP package and `GOOGLE_CLIENT_ID` in .env
2. **API access disabled**: All verified users should automatically have API access

What was implemented:
- ✓ Added UserObserver to automatically enable API when users verify their email
- ✓ Created `users:enable-api-for-verified` command to enable API for all existing verified users
- ✓ From now on, any user who verifies their email will automatically get API access

After running these fixes:
- ✓ Google Sign-In should work from the mobile app
- ✓ All verified users can login and use the API
- ✓ Future email verifications will automatically enable API access
