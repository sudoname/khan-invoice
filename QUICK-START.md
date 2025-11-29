# Khan Invoice - Quick Start Deployment

## 🚀 Deploy to Digital Ocean in 15 Minutes

### Step 1: Connect to Your Server
```bash
ssh root@147.182.242.177
```

### Step 2: Run One-Command Setup
```bash
curl -o setup.sh https://raw.githubusercontent.com/sudoname/khan-invoice/main/deploy-server-setup.sh && chmod +x setup.sh && ./setup.sh
```

Wait 5-10 minutes for installation...

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

### Step 4: Clone and Deploy
```bash
cd /var/www
git clone git@github.com:sudoname/khan-invoice.git kinvoice.ng
cd kinvoice.ng

# Configure Nginx
cp nginx-kinvoice.conf /etc/nginx/sites-available/kinvoice.ng
ln -s /etc/nginx/sites-available/kinvoice.ng /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

# Run deployment
chmod +x deploy.sh
./deploy.sh
```

### Step 5: Configure Environment
```bash
nano .env
```

Update these lines:
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

### Step 6: Create Admin User
```bash
php artisan make:admin
```

Enter your details when prompted.

### Step 7: Configure SSL
```bash
certbot --nginx -d kinvoice.ng -d www.kinvoice.ng
```

Select option 2 (Redirect HTTP to HTTPS).

### Step 8: Done! 🎉
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

## Important Security Notes

1. **Change MySQL root password immediately:**
```bash
mysql -u root -ptemp_password_change_later
ALTER USER 'root'@'localhost' IDENTIFIED BY 'YourNewStrongPassword#123';
exit;
```

2. **Enable firewall:**
```bash
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw enable
```

3. **Set up backups** (see DEPLOYMENT.md)

## Need Help?

See the full [DEPLOYMENT.md](DEPLOYMENT.md) guide for detailed instructions.
