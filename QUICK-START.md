# Khan Invoice - Quick Start Deployment

## 🚀 Deploy to Digital Ocean (Multi-Site Environment)

**Important:** This guide is for servers with existing sites. The application will be installed in `/var/www/kinvoice.ng`

### Step 1: Connect to Your Server
```bash
ssh root@147.182.242.177
```

### Step 2: Run Setup Script
```bash
curl -o setup.sh https://raw.githubusercontent.com/sudoname/khan-invoice/main/deploy-server-setup.sh && chmod +x setup.sh && sudo bash setup.sh
```

The script will:
- Check for existing installations (PHP, MySQL, Nginx, etc.)
- Install only missing components
- Create dedicated database: `khan_invoice`
- Create directory: `/var/www/kinvoice.ng`

Enter your MySQL root password when prompted.

### Step 3: Set Up SSH for GitHub (if not already configured)
```bash
# Generate SSH key
ssh-keygen -t ed25519 -C "server@kinvoice.ng" -f ~/.ssh/id_ed25519 -N ""

# Display public key
cat ~/.ssh/id_ed25519.pub
```

Copy the output and add it to GitHub:
1. Go to https://github.com/settings/keys
2. Click "New SSH key"
3. Paste the key and save

### Step 4: Clone Repository
```bash
cd /var/www
git clone git@github.com:sudoname/khan-invoice.git kinvoice.ng
cd kinvoice.ng
```

### Step 5: Configure Nginx (Add New Site)
```bash
# Copy Nginx configuration for kinvoice.ng
sudo cp nginx-kinvoice.conf /etc/nginx/sites-available/kinvoice.ng
sudo ln -s /etc/nginx/sites-available/kinvoice.ng /etc/nginx/sites-enabled/

# Test configuration (make sure it doesn't conflict with other sites)
sudo nginx -t

# If test passes, reload
sudo systemctl reload nginx
```

### Step 6: Run Deployment Script
```bash
chmod +x deploy.sh
sudo ./deploy.sh
```

This will install dependencies, build assets, and run migrations.

### Step 7: Configure Environment
```bash
nano .env
```

Verify/update these critical values:
```env
APP_URL=https://kinvoice.ng
DB_DATABASE=khan_invoice
DB_USERNAME=khan_invoice_user
DB_PASSWORD=KhanInv0ice#2025!Strong
```

Save (Ctrl+X, Y, Enter)

```bash
php artisan config:cache
```

### Step 8: Create Admin User
```bash
php artisan make:admin
```

Enter your details when prompted.

### Step 9: Configure SSL (if not already configured)
```bash
sudo certbot --nginx -d kinvoice.ng -d www.kinvoice.ng
```

Select option 2 (Redirect HTTP to HTTPS).

**Note:** If you already have other sites with SSL, certbot will handle multiple certificates automatically.

### Step 10: Done! 🎉
Visit https://kinvoice.ng

Login at: https://kinvoice.ng/app

## Troubleshooting

### If website shows 502 Bad Gateway:
```bash
systemctl status php8.3-fpm
systemctl restart php8.3-fpm nginx
```

### If database connection fails:
```bash
# Verify database exists
mysql -u khan_invoice_user -pKhanInv0ice#2025!Strong khan_invoice -e "SHOW TABLES;"
```

### Check logs:
```bash
tail -f /var/www/kinvoice.ng/storage/logs/laravel.log
tail -f /var/log/nginx/error.log
```

### Permission issues:
```bash
cd /var/www/kinvoice.ng
chown -R www-data:www-data .
chmod -R 755 .
chmod -R 775 storage bootstrap/cache
```

## Important Notes for Multi-Site Environment

1. **Database Isolation:** Khan Invoice uses its own database (`khan_invoice`) and won't interfere with other site databases.

2. **PHP-FPM Pool:** All sites share PHP 8.3-FPM. For better isolation, you can create a dedicated pool (see DEPLOYMENT.md).

3. **Nginx Configuration:** The kinvoice.ng site config won't conflict with existing sites.

4. **SSL Certificates:** Certbot manages multiple certificates automatically.

5. **File Permissions:** Make sure `/var/www/kinvoice.ng` has proper ownership:
```bash
sudo chown -R www-data:www-data /var/www/kinvoice.ng
sudo chmod -R 755 /var/www/kinvoice.ng
sudo chmod -R 775 /var/www/kinvoice.ng/storage
sudo chmod -R 775 /var/www/kinvoice.ng/bootstrap/cache
```

## Need Help?

See the full [DEPLOYMENT.md](DEPLOYMENT.md) guide for detailed instructions.
