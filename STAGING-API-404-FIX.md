# Fix: API Routes Return 404 on Staging

## Problem
- Web routes work: `https://staging.kinvoice.ng/app/api-settings` ✓
- API routes fail: `https://staging.kinvoice.ng/api/v1/...` ✗ (404 error)

## Root Cause
API routes are not being registered/cached properly on staging server.

---

## Quick Fix (Run on Staging Server)

SSH into staging and run these commands:

```bash
cd /var/www/staging.kinvoice.ng

# Step 1: Verify API routes file exists
ls -l routes/api.php

# Step 2: Clear ALL caches
php artisan optimize:clear

# Step 3: Verify routes are registered
php artisan route:list --path=api/v1 | head -10

# Step 4: Cache configuration and routes for production
php artisan config:cache
php artisan route:cache

# Step 5: Restart services
sudo systemctl restart php8.3-fpm
sudo systemctl reload nginx
```

---

## Detailed Fix (If Quick Fix Doesn't Work)

### Step 1: Verify Bootstrap Configuration

Check that `bootstrap/app.php` includes API routes:

```bash
cd /var/www/staging.kinvoice.ng
grep "api: __DIR__" bootstrap/app.php
```

**Expected output:**
```
api: __DIR__.'/../routes/api.php',
```

If NOT found, you need to pull latest code:
```bash
git pull origin main
```

### Step 2: Test Route Registration Internally

```bash
php artisan tinker
```

Then paste this:
```php
$route = Route::getRoutes()->match(
    \Illuminate\Http\Request::create('/api/v1/auth/login', 'POST')
);
echo "Route found: " . $route->getActionName();
exit
```

If this **works**, routes are registered - the issue is web server caching.
If this **fails**, routes are not loaded - check bootstrap/app.php.

### Step 3: Check Web Server Cache

**For Nginx with FastCGI Cache:**
```bash
# Clear FastCGI cache if enabled
sudo rm -rf /var/cache/nginx/*
sudo systemctl reload nginx
```

**For OpCache:**
```bash
# Clear PHP OpCache
sudo systemctl restart php8.3-fpm
```

### Step 4: Test Endpoint

```bash
curl -v https://staging.kinvoice.ng/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"test","password":"test"}'
```

**Expected:** HTTP 422 (validation error) or 401 (unauthorized)
**NOT Expected:** HTTP 404 (route not found)

---

## Automated Test Script

Run the diagnostic script:

```bash
cd /var/www/staging.kinvoice.ng
bash test-api-routes.sh
```

This will:
- ✓ Verify bootstrap/app.php configuration
- ✓ Check if routes/api.php exists
- ✓ List all registered API routes
- ✓ Test route resolution programmatically
- ✓ Test HTTP accessibility

---

## Common Issues & Solutions

### Issue 1: Routes Work Locally But Not on Staging
**Cause:** Stale route cache on staging
**Fix:**
```bash
php artisan route:clear
php artisan route:cache
sudo systemctl restart php8.3-fpm
```

### Issue 2: "bootstrap/app.php not found" Error
**Cause:** Using old Laravel version bootstrap structure
**Fix:** Update to Laravel 11 bootstrap structure or check if using old RouteServiceProvider

### Issue 3: Routes Return 404 Even After Cache Clear
**Cause:** Web server (nginx/apache) not passing requests to Laravel
**Fix:** Check nginx configuration:

```nginx
# /etc/nginx/sites-available/staging.kinvoice.ng
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

Then:
```bash
sudo nginx -t
sudo systemctl reload nginx
```

### Issue 4: Routes Work via Tinker But Not HTTP
**Cause:** PHP-FPM not restarted after changes
**Fix:**
```bash
sudo systemctl restart php8.3-fpm
sudo systemctl status php8.3-fpm  # Verify it's running
```

---

## Verification Checklist

After applying fixes, verify these work:

```bash
# 1. Login endpoint (should return 422 validation error)
curl https://staging.kinvoice.ng/api/v1/auth/login \
  -H "Content-Type: application/json"

# 2. Dashboard endpoint (should return 401 unauthorized)
curl https://staging.kinvoice.ng/api/v1/dashboard \
  -H "Accept: application/json"

# 3. Customers endpoint (should return 401 unauthorized)
curl https://staging.kinvoice.ng/api/v1/customers \
  -H "Accept: application/json"
```

All should return **JSON responses**, NOT 404 HTML pages.

---

## If Nothing Works

1. **Check Laravel logs:**
   ```bash
   tail -f /var/www/staging.kinvoice.ng/storage/logs/laravel.log
   ```

2. **Check nginx error logs:**
   ```bash
   sudo tail -f /var/log/nginx/error.log
   ```

3. **Check PHP-FPM logs:**
   ```bash
   sudo journalctl -u php8.3-fpm -n 50
   ```

4. **Verify file permissions:**
   ```bash
   ls -la /var/www/staging.kinvoice.ng/bootstrap/cache
   ls -la /var/www/staging.kinvoice.ng/storage
   ```

   Should be owned by `www-data:www-data` with `775` permissions.

---

## Final Notes

- Web routes work because they're loaded from `routes/web.php`
- API routes need `routes/api.php` to be loaded via `bootstrap/app.php`
- Laravel 11 changed routing configuration from `RouteServiceProvider` to `bootstrap/app.php`
- Always clear caches when debugging routing issues
- Route cache (`route:cache`) should be used in production for performance

**Most Common Fix:** Just run `php artisan optimize:clear && php artisan config:cache && php artisan route:cache && sudo systemctl restart php8.3-fpm`
