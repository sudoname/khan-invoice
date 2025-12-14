# How to Change VAT Rate in Production

## Current VAT Configuration

**Current Rate:** 7.5%

**Where VAT is Stored:**
1. **Settings Table** (Primary) - `settings` table with key `default_vat_rate`
2. **Business Profiles** - Each business can have their own `default_vat_rate`
3. **Invoices** - Individual invoices store their VAT rate at creation time
4. **Marketing Pages** - Hardcoded text in views (informational only)

---

## ✅ Method 1: Change via Tinker (Recommended - Quick)

### On Production Server

```bash
# SSH into production
ssh user@kinvoice.ng

# Navigate to project
cd /var/www/kinvoice.ng

# Open tinker
php artisan tinker

# Change default VAT rate (example: change to 8.0%)
>>> \App\Models\Setting::set('default_vat_rate', '8.0', 'number');
>>> exit

# Clear cache to apply immediately
php artisan cache:clear
```

**Verification:**
```bash
php artisan tinker
>>> \App\Models\Setting::getDefaultVatRate()
# Should return: 8.0
>>> exit
```

---

## ✅ Method 2: Direct Database Update

### Using SQL

```bash
# SSH into production
ssh user@kinvoice.ng

# Access database
mysql -u username -p database_name
# Or: psql -U username -d database_name

# Update setting
UPDATE settings
SET value = '8.0', updated_at = NOW()
WHERE key = 'default_vat_rate';

# Verify
SELECT * FROM settings WHERE key = 'default_vat_rate';

# Exit database
exit;

# Clear cache
php artisan cache:clear
```

---

## ✅ Method 3: Create Admin Command (Best for Future Changes)

### Create Command File

```php
<?php
// app/Console/Commands/UpdateVatRate.php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;

class UpdateVatRate extends Command
{
    protected $signature = 'vat:update {rate}';
    protected $description = 'Update the default VAT rate';

    public function handle()
    {
        $rate = $this->argument('rate');

        if (!is_numeric($rate) || $rate < 0 || $rate > 100) {
            $this->error('Invalid VAT rate. Must be between 0 and 100.');
            return 1;
        }

        Setting::set('default_vat_rate', $rate, 'number');

        $this->info("VAT rate updated to {$rate}%");

        // Clear cache
        $this->call('cache:clear');

        $this->info('Cache cleared. New VAT rate is now active.');

        return 0;
    }
}
```

### Usage on Production

```bash
# SSH into production
ssh user@kinvoice.ng
cd /var/www/kinvoice.ng

# Upload the command file to app/Console/Commands/UpdateVatRate.php

# Run the command
php artisan vat:update 8.0

# Output:
# VAT rate updated to 8.0%
# Cache cleared. New VAT rate is now active.
```

---

## 📊 What Changes and What Doesn't

### ✅ Will Use New VAT Rate
- **New invoices** created after the change
- **New public invoices** generated
- **Invoice forms** (default value)
- **Business profile forms** (default value)

### ❌ Will NOT Change Automatically
- **Existing invoices** - They keep their original VAT rate
- **Business profiles** - Each business has their own rate (change individually)
- **Marketing pages** - Text like "VAT (7.5%)" is hardcoded

---

## 🔄 Update Existing Business Profiles (Optional)

If you want ALL businesses to use the new rate:

```bash
php artisan tinker

# Update all business profiles
>>> \App\Models\BusinessProfile::query()->update(['default_vat_rate' => 8.0]);

# Verify
>>> \App\Models\BusinessProfile::pluck('default_vat_rate')->unique()
# Should show: [8.0]

>>> exit
```

---

## 📝 Update Marketing Pages (Optional)

If you want to update the hardcoded text in marketing pages:

### Files to Update

```
resources/views/home.blade.php
resources/views/pages/about.blade.php
resources/views/pages/faq.blade.php
resources/views/public-invoice/create.blade.php
```

### Using Search & Replace

```bash
# On production server
cd /var/www/kinvoice.ng

# Search for all occurrences
grep -r "7.5%" resources/views/ --include="*.blade.php"

# Replace 7.5% with 8.0% in specific files
sed -i 's/7\.5%/8.0%/g' resources/views/home.blade.php
sed -i 's/7\.5%/8.0%/g' resources/views/pages/about.blade.php
sed -i 's/7\.5%/8.0%/g' resources/views/pages/faq.blade.php
sed -i 's/7\.5/8.0/g' resources/views/public-invoice/create.blade.php

# Clear view cache
php artisan view:clear
```

**OR commit changes locally and push:**

```bash
# On local machine
cd C:/Users/yomi/khan-invoice

# Update files
sed -i 's/7\.5%/8.0%/g' resources/views/home.blade.php
sed -i 's/7\.5%/8.0%/g' resources/views/pages/about.blade.php
sed -i 's/7\.5%/8.0%/g' resources/views/pages/faq.blade.php
sed -i 's/7\.5/8.0/g' resources/views/public-invoice/create.blade.php

# Commit and push
git add resources/views/
git commit -m "update: change VAT rate from 7.5% to 8.0%"
git push origin main

# Then on production
git pull origin main
php artisan view:clear
```

---

## 🧪 Testing the Change

### Test on Staging First

```bash
# On staging.kinvoice.ng
php artisan tinker
>>> \App\Models\Setting::set('default_vat_rate', '8.0', 'number');
>>> exit
php artisan cache:clear

# Create a test invoice and verify VAT calculation
```

### Verify on Production

```bash
# Check current rate
php artisan tinker
>>> \App\Models\Setting::getDefaultVatRate()
# Should return new rate

# Check a few business profiles
>>> \App\Models\BusinessProfile::first()->default_vat_rate
# Returns individual business rate

>>> exit
```

---

## 📋 Complete Production Change Checklist

### Pre-Change
- [ ] Backup database: `mysqldump -u user -p dbname > backup_$(date +%Y%m%d).sql`
- [ ] Test on staging first
- [ ] Notify team about change
- [ ] Plan change during low-traffic period

### Change Execution
- [ ] SSH into production server
- [ ] Update settings table via tinker or SQL
- [ ] Clear application cache
- [ ] Verify setting updated correctly
- [ ] (Optional) Update business profiles
- [ ] (Optional) Update marketing pages

### Post-Change
- [ ] Create test invoice and verify VAT calculation
- [ ] Check public invoice generator
- [ ] Monitor application logs
- [ ] Notify team change is complete

---

## 🚨 Rollback Plan

If something goes wrong:

```bash
# Revert to 7.5%
php artisan tinker
>>> \App\Models\Setting::set('default_vat_rate', '7.5', 'number');
>>> exit
php artisan cache:clear

# Or via SQL
mysql> UPDATE settings SET value = '7.5' WHERE key = 'default_vat_rate';

# Clear cache
php artisan cache:clear
```

---

## 💡 Best Practice Approach

### Recommended Steps:

1. **Test on Staging:**
   ```bash
   # staging.kinvoice.ng
   php artisan vat:update 8.0
   # Test thoroughly
   ```

2. **Update Production:**
   ```bash
   # kinvoice.ng (production)
   php artisan tinker
   >>> \App\Models\Setting::set('default_vat_rate', '8.0', 'number');
   >>> exit
   php artisan cache:clear
   ```

3. **Update Marketing Pages:**
   ```bash
   # On local machine - commit changes
   # On production - pull changes
   git pull origin main
   php artisan view:clear
   ```

4. **Verify:**
   ```bash
   php artisan tinker
   >>> \App\Models\Setting::getDefaultVatRate()
   >>> exit
   ```

---

## 📊 Impact Summary

### What Changes Immediately
✅ Default VAT rate for new invoices
✅ Default value in invoice forms
✅ Default value in business profile forms
✅ API responses using default VAT

### What Requires Additional Steps
⚠️ Existing business profiles (optional bulk update)
⚠️ Marketing page text (optional, cosmetic)
⚠️ Existing invoices (never changed - historical data)

### What Never Changes
❌ Existing invoices (they are historical records)
❌ Completed transactions (finalized data)

---

## 🔍 Verification Script

Save this as `check_vat_rate.php`:

```php
<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\nVAT Rate Configuration Check\n";
echo "============================\n\n";

// Default system rate
$defaultRate = \App\Models\Setting::getDefaultVatRate();
echo "System Default VAT Rate: {$defaultRate}%\n\n";

// Business profiles
$profiles = \App\Models\BusinessProfile::select('id', 'business_name', 'default_vat_rate')
    ->limit(5)
    ->get();

echo "Sample Business Profiles:\n";
foreach ($profiles as $profile) {
    echo "  - {$profile->business_name}: {$profile->default_vat_rate}%\n";
}

echo "\nDone!\n";
```

**Run on production:**
```bash
php check_vat_rate.php
```

---

## 📞 Support

**If Issues Arise:**
1. Check `storage/logs/laravel.log`
2. Verify cache was cleared: `php artisan cache:clear`
3. Check database: `SELECT * FROM settings WHERE key = 'default_vat_rate'`
4. Test invoice creation
5. Rollback if needed (revert to 7.5%)

---

## 📝 Summary

**Quick Change on Production:**
```bash
ssh user@kinvoice.ng
cd /var/www/kinvoice.ng
php artisan tinker
>>> \App\Models\Setting::set('default_vat_rate', '8.0', 'number');
>>> exit
php artisan cache:clear
```

**Verification:**
```bash
php artisan tinker
>>> \App\Models\Setting::getDefaultVatRate()
>>> exit
```

**That's it!** New invoices will use the new VAT rate. 🎉

---

**Note:** The subscription upgrade/downgrade changes we made are on **staging only**. They haven't been deployed to **production** yet. This VAT change guide works for **both staging and production** independently.
