# Staging Deployment Guide

## Quick Deployment (Recommended)

### Option 1: Using the Deployment Script (Easiest)

SSH into your staging server and run:

```bash
# SSH into staging server
ssh your-user@staging.kinvoice.ng

# Navigate to the application directory
cd /var/www/staging.kinvoice.ng

# Pull latest deployment script
git pull origin main

# Make script executable
chmod +x deploy-staging.sh

# Run deployment
sudo bash deploy-staging.sh
```

The script will automatically:
1. ✅ Pull latest changes from GitHub
2. ✅ Install dependencies (Composer + NPM)
3. ✅ Build frontend assets
4. ✅ Run migrations
5. ✅ Seed currencies
6. ✅ Clear and optimize caches
7. ✅ Set permissions
8. ✅ Restart services

---

## Copying Production Database to Staging

If you need to copy production data to staging for testing:

### Using the Database Copy Script

```bash
# SSH into staging server
ssh your-user@staging.kinvoice.ng

# Navigate to application directory
cd /var/www/staging.kinvoice.ng

# Pull latest scripts
git pull origin main

# Make script executable
chmod +x copy-prod-to-staging.sh

# Run database copy (with automatic backup)
sudo bash copy-prod-to-staging.sh
```

**What the script does:**
1. ✅ **Reads database credentials from .env files** (no hardcoded values!)
2. ✅ **Backs up PRODUCTION database** (safety first!)
3. ✅ **Backs up STAGING database** (double safety!)
4. ✅ Drops and recreates staging database
5. ✅ Imports production data to staging
6. ✅ Updates environment-specific data
7. ✅ Runs any new migrations
8. ✅ Clears caches

**Features:**
- Automatically reads `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` from:
  - Production: `/var/www/kinvoice.ng/.env`
  - Staging: `/var/www/staging.kinvoice.ng/.env`
- Both databases backed up before any changes
- No manual configuration needed!

### Manual Database Copy (Alternative)

If you prefer manual control, read credentials from .env files:

```bash
# Helper function to read .env values
get_env_value() {
    grep "^${2}=" "$1" | cut -d '=' -f2- | tr -d '"' | tr -d "'"
}

# Read production database credentials
PROD_DB=$(get_env_value "/var/www/kinvoice.ng/.env" "DB_DATABASE")
PROD_USER=$(get_env_value "/var/www/kinvoice.ng/.env" "DB_USERNAME")
PROD_PASS=$(get_env_value "/var/www/kinvoice.ng/.env" "DB_PASSWORD")

# Read staging database credentials
STAGING_DB=$(get_env_value "/var/www/staging.kinvoice.ng/.env" "DB_DATABASE")
STAGING_USER=$(get_env_value "/var/www/staging.kinvoice.ng/.env" "DB_USERNAME")
STAGING_PASS=$(get_env_value "/var/www/staging.kinvoice.ng/.env" "DB_PASSWORD")

# 1. Backup PRODUCTION database first (safety!)
mysqldump -u "$PROD_USER" --password="$PROD_PASS" "$PROD_DB" | gzip > /var/backups/production_backup_$(date +%Y%m%d_%H%M%S).sql.gz

# 2. Backup STAGING database (double safety!)
mysqldump -u "$STAGING_USER" --password="$STAGING_PASS" "$STAGING_DB" | gzip > /var/backups/staging_backup_$(date +%Y%m%d_%H%M%S).sql.gz

# 3. Use the production backup for import
PROD_BACKUP="/var/backups/production_backup_$(date +%Y%m%d_%H%M%S).sql.gz"

# 4. Drop and recreate staging database
mysql -u "$STAGING_USER" --password="$STAGING_PASS" -e "DROP DATABASE IF EXISTS \`$STAGING_DB\`; CREATE DATABASE \`$STAGING_DB\`;"

# 5. Import production data to staging
gunzip < $PROD_BACKUP | mysql -u "$STAGING_USER" --password="$STAGING_PASS" "$STAGING_DB"

# 6. Run migrations and clear cache
cd /var/www/staging.kinvoice.ng
php artisan migrate --force
php artisan optimize:clear
```

### Important Notes

⚠️ **Before copying database:**
- **BOTH production and staging databases are backed up automatically**
- Production data may contain sensitive information
- Test notifications won't affect production customers (different .env settings)
- Backup location: `/var/backups/khan-invoice/`
- Both backups are timestamped and compressed

💡 **After copying database:**
- Update `.env` for staging-specific settings
- Verify notification settings don't send to real customers
- Test with dummy data if possible
- All backups are timestamped for easy restoration

### Restore from Backups

If something goes wrong, both databases can be restored. The script reads credentials from .env files, so you should too:

```bash
# Helper function to read .env values
get_env_value() {
    grep "^${2}=" "$1" | cut -d '=' -f2- | tr -d '"' | tr -d "'"
}

# List available backups
ls -lh /var/backups/khan-invoice/

# Read credentials from .env files
PROD_DB=$(get_env_value "/var/www/kinvoice.ng/.env" "DB_DATABASE")
PROD_USER=$(get_env_value "/var/www/kinvoice.ng/.env" "DB_USERNAME")
PROD_PASS=$(get_env_value "/var/www/kinvoice.ng/.env" "DB_PASSWORD")

STAGING_DB=$(get_env_value "/var/www/staging.kinvoice.ng/.env" "DB_DATABASE")
STAGING_USER=$(get_env_value "/var/www/staging.kinvoice.ng/.env" "DB_USERNAME")
STAGING_PASS=$(get_env_value "/var/www/staging.kinvoice.ng/.env" "DB_PASSWORD")

# Restore STAGING from backup
gunzip < /var/backups/khan-invoice/staging_backup_TIMESTAMP.sql.gz | mysql -u "$STAGING_USER" --password="$STAGING_PASS" "$STAGING_DB"

# Restore PRODUCTION from backup (if needed)
gunzip < /var/backups/khan-invoice/production_backup_TIMESTAMP.sql.gz | mysql -u "$PROD_USER" --password="$PROD_PASS" "$PROD_DB"

# Clear cache after restore
cd /var/www/staging.kinvoice.ng
php artisan optimize:clear
```

**Note:** Both databases are backed up before the copy, so both can be restored if needed. Credentials are always read from .env files.

---

## Option 2: Manual Deployment Steps

If you prefer manual control, follow these steps:

### 1. SSH into Staging Server

```bash
ssh your-user@staging.kinvoice.ng
cd /var/www/staging.kinvoice.ng
```

### 2. Pull Latest Changes

```bash
git pull origin main
```

### 3. Install Dependencies

```bash
# Composer
composer install --no-dev --optimize-autoloader

# NPM
npm install
```

### 4. Build Assets

```bash
npm run build
```

### 5. Run Migrations

```bash
php artisan migrate --force
```

### 6. Seed Currencies (First time only)

```bash
php artisan db:seed --class=GlobalCurrenciesSeeder --force
```

### 7. Clear & Optimize

```bash
# Clear all caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 8. Set Permissions

```bash
sudo chown -R www-data:www-data /var/www/staging.kinvoice.ng
sudo chmod -R 755 /var/www/staging.kinvoice.ng
sudo chmod -R 775 /var/www/staging.kinvoice.ng/storage
sudo chmod -R 775 /var/www/staging.kinvoice.ng/bootstrap/cache
```

### 9. Restart Services

```bash
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx

# If queue worker is configured
sudo systemctl restart laravel-worker

# If scheduler is configured
sudo systemctl restart laravel-scheduler
```

---

## Post-Deployment Configuration

### 1. Update Environment Variables

Edit the `.env` file on staging:

```bash
nano /var/www/staging.kinvoice.ng/.env
```

Add the following API keys:

```env
# Termii SMS (Get from https://termii.com)
TERMII_API_KEY=your_termii_api_key_here
TERMII_SENDER_ID=KhanInvoice

# Twilio WhatsApp (Get from https://twilio.com)
TWILIO_ACCOUNT_SID=your_twilio_account_sid
TWILIO_AUTH_TOKEN=your_twilio_auth_token
TWILIO_WHATSAPP_FROM=+14155238886

# Queue (if not already set)
QUEUE_CONNECTION=database
```

After updating `.env`, clear config cache:

```bash
php artisan config:clear
php artisan config:cache
```

### 2. Set Up Cron Job for Scheduler

Edit crontab:

```bash
sudo crontab -e -u www-data
```

Add this line:

```cron
* * * * * cd /var/www/staging.kinvoice.ng && php artisan schedule:run >> /dev/null 2>&1
```

This enables:
- ⏰ Payment reminders (daily at 9 AM)
- 🚨 Overdue invoice checks (daily at 10 AM)

### 3. Set Up Queue Worker (Optional but Recommended)

Create systemd service:

```bash
sudo nano /etc/systemd/system/laravel-worker.service
```

Add:

```ini
[Unit]
Description=Laravel Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/staging.kinvoice.ng/artisan queue:work --tries=3

[Install]
WantedBy=multi-user.target
```

Enable and start:

```bash
sudo systemctl enable laravel-worker
sudo systemctl start laravel-worker
sudo systemctl status laravel-worker
```

---

## Verification Checklist

After deployment, verify everything works:

### 1. Check Website Loads

```bash
curl -I https://staging.kinvoice.ng
# Should return: HTTP/2 200
```

### 2. Test Admin Panel

Visit: https://staging.kinvoice.ng/app
- ✅ Login works
- ✅ Dashboard loads
- ✅ Navigation menu shows new items

### 3. Verify New Features

#### Multi-Currency
- Go to: Invoices → Create Invoice
- ✅ Currency dropdown shows 50+ currencies
- ✅ Can select USD, EUR, GBP, NGN, etc.

#### Notification Settings
- Go to: Settings → Notification Settings
- ✅ SMS section visible
- ✅ WhatsApp section visible
- ✅ Can enable/disable notifications

#### API Settings
- Go to: Settings → API Settings
- ✅ Page loads without errors
- ✅ Can enable API access
- ✅ Can create API token

### 4. Test API Endpoints

```bash
# Create token
curl -X POST https://staging.kinvoice.ng/api/v1/auth/token \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "your_password",
    "token_name": "Test Token"
  }'

# Test invoice endpoint
curl https://staging.kinvoice.ng/api/v1/invoices \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### 5. Check Database Migrations

```bash
php artisan migrate:status
```

Should show all migrations as "Ran".

### 6. Verify Currencies Seeded

```bash
php artisan tinker
>> \App\Models\Currency::count();
# Should return: 50
```

### 7. Check Logs for Errors

```bash
tail -f /var/www/staging.kinvoice.ng/storage/logs/laravel.log
```

---

## Rollback (If Needed)

If something goes wrong, rollback to previous version:

```bash
cd /var/www/staging.kinvoice.ng

# View commit history
git log --oneline -10

# Rollback to previous commit
git reset --hard <previous-commit-hash>

# Revert migrations
php artisan migrate:rollback

# Clear caches
php artisan optimize:clear

# Restart services
sudo systemctl restart php8.3-fpm
```

---

## Common Issues & Solutions

### Issue 1: Migration Fails

**Error:** "SQLSTATE[HY000]: General error: 1 table already exists"

**Solution:**
```bash
# Check which migrations have run
php artisan migrate:status

# If specific migration failed, rollback and retry
php artisan migrate:rollback --step=1
php artisan migrate
```

### Issue 2: Permission Denied

**Error:** "The stream or file could not be opened"

**Solution:**
```bash
sudo chown -R www-data:www-data /var/www/staging.kinvoice.ng
sudo chmod -R 775 /var/www/staging.kinvoice.ng/storage
sudo chmod -R 775 /var/www/staging.kinvoice.ng/bootstrap/cache
```

### Issue 3: API Returns 500 Error

**Solution:**
```bash
# Clear all caches
php artisan optimize:clear
php artisan config:cache

# Check logs
tail -100 storage/logs/laravel.log
```

### Issue 4: Queue Not Processing

**Solution:**
```bash
# Restart queue worker
sudo systemctl restart laravel-worker

# Check status
sudo systemctl status laravel-worker

# View logs
sudo journalctl -u laravel-worker -f
```

### Issue 5: Scheduler Not Running

**Solution:**
```bash
# Test scheduler manually
php artisan schedule:list
php artisan schedule:run

# Verify cron job
sudo crontab -l -u www-data
```

---

## Features Deployed

### ✅ Multi-Currency Support
- 50 global currencies
- USD, EUR, GBP, NGN, GHS, INR, JPY, CAD, AUD, and more
- Global invoicing platform

### ✅ SMS Notifications (Termii)
- Payment received
- Invoice sent
- Payment reminders
- Invoice overdue alerts
- Credit tracking system

### ✅ WhatsApp Notifications (Twilio)
- Payment received with emoji formatting
- Invoice sent notifications
- Payment reminders with urgency levels
- Overdue invoice alerts
- Credit tracking system

### ✅ Automatic Reminders
- Payment reminders (3 days before + day of due date)
- Overdue invoice checks (daily)
- Scheduled via Laravel scheduler
- Queued for performance

### ✅ REST API Integration
- Sanctum authentication
- Token management
- Full CRUD for Invoices, Customers, Payments
- Sales, Aging, and P&L reports
- Rate limiting per user
- API Settings UI in Filament

### ✅ Enhanced Notification System
- 4 notification types
- Multi-channel (Email, SMS, WhatsApp)
- Per-user preferences
- Credit tracking
- Comprehensive logging

---

## Support & Testing

### Run Test Suites

```bash
# Test currencies
php test-currencies.php

# Test SMS integration
php test-sms.php

# Test WhatsApp integration
php test-week6.php

# Test API integration
php test-api.php
```

### Check System Health

```bash
# PHP version
php -v

# Composer version
composer --version

# Node version
node -v

# NPM version
npm -v

# Database connection
php artisan tinker
>> DB::connection()->getPdo();
```

---

## Deployment Summary

**Total Files Changed:** 58 files
**Lines Added:** 7,235+
**Lines Removed:** 73-

**Database Migrations:** 6 new migrations
**New Models:** 3 (NotificationPreference, SmsLog, WhatsAppLog)
**New Controllers:** 5 API controllers
**New Commands:** 3 (reminders, overdue check, test)
**New Jobs:** 2 (reminder, overdue jobs)
**New Notifications:** 4 (payment, invoice, reminder, overdue)
**New Services:** 2 (Termii, WhatsApp)
**New Middleware:** 1 (API rate limit)
**New Resources:** 3 (Invoice, Customer, Payment)

---

## Next Steps After Deployment

1. ✅ Test all new features manually
2. ✅ Configure API keys (Termii, Twilio)
3. ✅ Set up cron job for scheduler
4. ✅ Set up queue worker (optional)
5. ✅ Test API endpoints with Postman
6. ✅ Add SMS/WhatsApp credits to test account
7. ✅ Send test notifications
8. ✅ Monitor logs for 24 hours
9. ✅ Deploy to production when stable

---

**Deployment Date:** December 12, 2025
**Version:** v2.0.0
**Environment:** Staging (staging.kinvoice.ng)
