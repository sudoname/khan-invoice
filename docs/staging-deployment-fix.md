# Fix Staging Server API Routes

The staging server needs to be updated with the latest code and routes. Run these commands on the staging server:

## SSH into Staging Server
```bash
ssh username@staging.kinvoice.ng
```

## Navigate to Project Directory
```bash
cd /var/www/staging.kinvoice.ng
```

## Pull Latest Code
```bash
git pull origin main
# Or if you're using a different branch:
# git pull origin staging
```

## Update Dependencies
```bash
composer install --no-dev --optimize-autoloader
```

## Clear and Cache Routes
```bash
php artisan route:clear
php artisan route:cache
php artisan config:cache
php artisan optimize
```

## Set Correct Permissions
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

## Restart PHP-FPM (if using PHP-FPM)
```bash
sudo systemctl restart php8.2-fpm
# Or check your PHP version:
# sudo systemctl restart php8.3-fpm
```

## Verify Routes are Loaded
```bash
php artisan route:list | grep api/v1
```

This should show all your API routes like:
- POST api/v1/auth/login
- POST api/v1/auth/google
- GET api/v1/dashboard
- etc.

## Test API Endpoint
```bash
curl -X POST https://staging.kinvoice.ng/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"admin@khaninvoice.com","password":"your-password"}'
```

If this returns valid JSON (not a 404), the API is working!

---

## Common Issues:

1. **If git pull fails**: Make sure your staging branch is up to date
2. **If composer fails**: Check composer.json exists and PHP version matches
3. **If routes still don't work**: Check that bootstrap/app.php has the API routing configured (it should based on your local setup)
