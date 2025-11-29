# Khan Invoice - Deployment Guide

## Server Information
- **IP Address**: 147.182.242.177
- **Domain**: kinvoice.ng
- **OS**: Ubuntu 22.04 LTS (assumed)

## Prerequisites
- SSH access to the server
- Domain DNS pointing to server IP
- Root or sudo access

## Deployment Steps

### 1. Connect to Server
```bash
ssh root@147.182.242.177
```

### 2. Run Server Setup Script
```bash
# Download and run the setup script
curl -o setup.sh https://raw.githubusercontent.com/sudoname/khan-invoice/main/deploy-server-setup.sh
chmod +x setup.sh
./setup.sh
```

This will install:
- PHP 8.3 with required extensions
- MySQL 8.0
- Nginx
- Node.js 20.x
- Composer
- Certbot for SSL

### 3. Configure DNS
Before proceeding, ensure your domain DNS is configured:

**A Records:**
```
kinvoice.ng     →  147.182.242.177
www.kinvoice.ng →  147.182.242.177
```

Wait for DNS propagation (can take up to 48 hours, usually 15-30 minutes).

### 4. Clone Repository
```bash
cd /var/www
git clone git@github.com:sudoname/khan-invoice.git kinvoice.ng
cd kinvoice.ng
```

### 5. Configure Nginx
```bash
# Copy Nginx configuration
cp nginx-kinvoice.conf /etc/nginx/sites-available/kinvoice.ng
ln -s /etc/nginx/sites-available/kinvoice.ng /etc/nginx/sites-enabled/
rm /etc/nginx/sites-enabled/default  # Remove default site

# Test configuration
nginx -t

# Reload Nginx
systemctl reload nginx
```

### 6. Configure Environment
```bash
# Copy and edit environment file
cp .env.production.example .env
nano .env
```

Update these critical values in `.env`:
```env
APP_KEY=  # Will be generated automatically
APP_URL=https://kinvoice.ng
DB_DATABASE=khan_invoice
DB_USERNAME=khan_invoice_user
DB_PASSWORD=KhanInv0ice#2025!Strong
```

### 7. Run Deployment Script
```bash
chmod +x deploy.sh
./deploy.sh
```

This will:
- Install Composer dependencies
- Install Node dependencies
- Generate APP_KEY
- Create storage symlink
- Build frontend assets
- Run database migrations
- Optimize Laravel
- Set proper permissions

### 8. Create Admin User
```bash
php artisan make:admin
```

Follow the prompts to create your admin account.

### 9. Configure SSL Certificate
```bash
certbot --nginx -d kinvoice.ng -d www.kinvoice.ng
```

Follow the prompts. Select option 2 to redirect HTTP to HTTPS.

### 10. Verify Installation
Visit https://kinvoice.ng and you should see the Khan Invoice homepage.

## Post-Deployment

### Configure Firewall
```bash
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw enable
```

### Set Up Automatic Backups
```bash
# Create backup script
nano /root/backup-khan-invoice.sh
```

Paste:
```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/root/backups"
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u khan_invoice_user -pKhanInv0ice#2025!Strong khan_invoice > $BACKUP_DIR/db_$DATE.sql

# Backup uploaded files
tar -czf $BACKUP_DIR/storage_$DATE.tar.gz /var/www/kinvoice.ng/storage/app/public

# Keep only last 7 days
find $BACKUP_DIR -type f -mtime +7 -delete
```

Make executable and add to crontab:
```bash
chmod +x /root/backup-khan-invoice.sh
crontab -e
```

Add line:
```
0 2 * * * /root/backup-khan-invoice.sh
```

### Configure Mail (Optional)
Update `.env` with your mail provider settings:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=hello@kinvoice.ng
```

Then clear cache:
```bash
php artisan config:clear
php artisan config:cache
```

## Troubleshooting

### Permission Issues
```bash
chown -R www-data:www-data /var/www/kinvoice.ng
chmod -R 755 /var/www/kinvoice.ng
chmod -R 775 /var/www/kinvoice.ng/storage
chmod -R 775 /var/www/kinvoice.ng/bootstrap/cache
```

### Clear All Caches
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### View Logs
```bash
tail -f /var/www/kinvoice.ng/storage/logs/laravel.log
tail -f /var/log/nginx/error.log
```

### Restart Services
```bash
systemctl restart php8.3-fpm
systemctl restart nginx
systemctl restart mysql
```

## Updating the Application

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

## Security Recommendations

1. **Change default database passwords**
2. **Set up fail2ban** to prevent brute force attacks
3. **Enable automatic security updates**
4. **Regular backups** (daily recommended)
5. **Monitor logs** for suspicious activity
6. **Keep software updated** (PHP, MySQL, Nginx)

## Support

For issues, check:
- Laravel logs: `/var/www/kinvoice.ng/storage/logs/`
- Nginx logs: `/var/log/nginx/`
- PHP-FPM logs: `/var/log/php8.3-fpm.log`
