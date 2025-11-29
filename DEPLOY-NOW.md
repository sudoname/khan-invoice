# Deploy Khan Invoice NOW - Manual Steps

## For Multi-Site Server (147.182.242.177)

### ⚡ Quick Deploy - Copy & Paste Commands

Connect to your server:
```bash
ssh root@147.182.242.177
```

---

## Step 1: Create Database
```bash
mysql -u root -p
```

Then run:
```sql
CREATE DATABASE IF NOT EXISTS khan_invoice CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'khan_invoice_user'@'localhost' IDENTIFIED BY 'KhanInv0ice#2025!Strong';
GRANT ALL PRIVILEGES ON khan_invoice.* TO 'khan_invoice_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## Step 2: Create Directory
```bash
mkdir -p /var/www/kinvoice.ng
cd /var/www/kinvoice.ng
```

---

## Step 3: Clone Repository
```bash
# If you have SSH key configured for GitHub:
git clone git@github.com:sudoname/khan-invoice.git .

# Or use HTTPS:
git clone https://github.com/sudoname/khan-invoice.git .
```

---

## Step 4: Install Dependencies
```bash
# Install Composer dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies
npm install
```

---

## Step 5: Configure Environment
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit environment file
nano .env
```

Update these values in .env:
```env
APP_NAME="Khan Invoice"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://kinvoice.ng

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=khan_invoice
DB_USERNAME=khan_invoice_user
DB_PASSWORD=KhanInv0ice#2025!Strong

SESSION_DOMAIN=.kinvoice.ng
```

Save: `Ctrl+X`, then `Y`, then `Enter`

---

## Step 6: Set Up Storage
```bash
php artisan storage:link
```

---

## Step 7: Build Assets
```bash
npm run build
```

---

## Step 8: Run Migrations
```bash
php artisan migrate --force
```

---

## Step 9: Optimize Laravel
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Step 10: Configure Nginx
```bash
# Copy the configuration
cp /var/www/kinvoice.ng/nginx-kinvoice.conf /etc/nginx/sites-available/kinvoice.ng

# Enable the site
ln -s /etc/nginx/sites-available/kinvoice.ng /etc/nginx/sites-enabled/

# Test configuration
nginx -t

# If test passes, reload
systemctl reload nginx
```

---

## Step 11: Set Permissions
```bash
chown -R www-data:www-data /var/www/kinvoice.ng
chmod -R 755 /var/www/kinvoice.ng
chmod -R 775 /var/www/kinvoice.ng/storage
chmod -R 775 /var/www/kinvoice.ng/bootstrap/cache
```

---

## Step 12: Create Admin User
```bash
cd /var/www/kinvoice.ng
php artisan make:admin
```

Follow the prompts to create your admin account.

---

## Step 13: Configure SSL (Optional but Recommended)
```bash
certbot --nginx -d kinvoice.ng -d www.kinvoice.ng
```

Select option 2 to redirect HTTP to HTTPS.

---

## Step 14: Restart Services
```bash
systemctl restart php8.3-fpm
systemctl restart nginx
```

---

## ✅ Done!

Visit: **https://kinvoice.ng** (or http://kinvoice.ng if SSL not configured yet)

Admin login: **https://kinvoice.ng/app**

---

## Troubleshooting

### If you get permission errors:
```bash
cd /var/www/kinvoice.ng
chown -R www-data:www-data .
chmod -R 755 .
chmod -R 775 storage bootstrap/cache
```

### If you get 502 Bad Gateway:
```bash
# Check PHP-FPM
systemctl status php8.3-fpm
systemctl restart php8.3-fpm

# Check logs
tail -f /var/www/kinvoice.ng/storage/logs/laravel.log
```

### If database connection fails:
```bash
# Test database connection
mysql -u khan_invoice_user -pKhanInv0ice#2025!Strong khan_invoice -e "SHOW TABLES;"

# If it works, clear Laravel cache
cd /var/www/kinvoice.ng
php artisan config:clear
php artisan config:cache
```

### View error logs:
```bash
# Laravel logs
tail -f /var/www/kinvoice.ng/storage/logs/laravel.log

# Nginx logs
tail -f /var/log/nginx/error.log

# PHP-FPM logs
tail -f /var/log/php8.3-fpm.log
```

---

## Update Application (Future)

To deploy updates:
```bash
cd /var/www/kinvoice.ng
git pull origin main
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
systemctl restart php8.3-fpm
```

---

## Security Checklist

- [ ] SSL certificate installed
- [ ] Changed MySQL root password
- [ ] Set APP_DEBUG=false in .env
- [ ] Set strong database password
- [ ] Firewall configured (UFW)
- [ ] Regular backups set up
- [ ] File permissions correct (755 for files, 775 for storage)

---

## Database Credentials (Save These!)

```
Database: khan_invoice
Username: khan_invoice_user
Password: KhanInv0ice#2025!Strong
Host: localhost
```

**IMPORTANT:** Change this password in production!

To change:
```bash
mysql -u root -p
```
```sql
ALTER USER 'khan_invoice_user'@'localhost' IDENTIFIED BY 'YourNewStrongPassword';
FLUSH PRIVILEGES;
```

Then update in `/var/www/kinvoice.ng/.env` and run:
```bash
php artisan config:cache
```
