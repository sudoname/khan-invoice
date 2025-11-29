#!/bin/bash

# Khan Invoice - Server Setup Script for Digital Ocean
# Domain: kinvoice.ng
# Server IP: 147.182.242.177
# NOTE: This script checks for existing installations

set -e  # Exit on error

echo "========================================="
echo "Khan Invoice - Server Setup"
echo "Multi-Site Environment"
echo "========================================="
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "Please run as root (use sudo)"
    exit 1
fi

# Install required packages only if not present
echo "Step 1: Checking and installing required packages..."
apt update

# Check if PHP 8.3 is installed
if ! command -v php8.3 &> /dev/null; then
    echo "Installing PHP 8.3..."
    add-apt-repository ppa:ondrej/php -y
    apt update
    apt install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-pgsql php8.3-sqlite3 \
        php8.3-curl php8.3-gd php8.3-mbstring php8.3-xml php8.3-zip php8.3-bcmath \
        php8.3-intl php8.3-redis php8.3-imagick php8.3-dom php8.3-fileinfo
else
    echo "✓ PHP 8.3 already installed"
fi

# Check if MySQL is installed
if ! command -v mysql &> /dev/null; then
    echo "Installing MySQL Server..."
    apt install -y mysql-server
else
    echo "✓ MySQL already installed"
fi

# Check if Nginx is installed
if ! command -v nginx &> /dev/null; then
    echo "Installing Nginx..."
    apt install -y nginx
else
    echo "✓ Nginx already installed"
fi

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    echo "Installing Node.js 20.x..."
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    apt install -y nodejs
else
    echo "✓ Node.js already installed (version: $(node -v))"
fi

# Check if Composer is installed
if ! command -v composer &> /dev/null; then
    echo "Installing Composer..."
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
else
    echo "✓ Composer already installed"
fi

# Check if Certbot is installed
if ! command -v certbot &> /dev/null; then
    echo "Installing Certbot..."
    apt install -y certbot python3-certbot-nginx
else
    echo "✓ Certbot already installed"
fi

# Install git if not present
apt install -y git curl wget unzip

# Configure MySQL database
echo ""
echo "Step 2: Setting up database for Khan Invoice..."
read -sp "Enter MySQL root password (or press Enter if no password set): " MYSQL_ROOT_PASS
echo ""

if [ -z "$MYSQL_ROOT_PASS" ]; then
    MYSQL_CMD="mysql"
else
    MYSQL_CMD="mysql -u root -p$MYSQL_ROOT_PASS"
fi

# Create database and user
$MYSQL_CMD <<EOF
CREATE DATABASE IF NOT EXISTS khan_invoice CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'khan_invoice_user'@'localhost' IDENTIFIED BY 'KhanInv0ice#2025!Strong';
GRANT ALL PRIVILEGES ON khan_invoice.* TO 'khan_invoice_user'@'localhost';
FLUSH PRIVILEGES;
EOF

echo "✓ Database created: khan_invoice"

# Create directory for the site
echo ""
echo "Step 3: Creating directory structure..."
mkdir -p /var/www/kinvoice.ng
chown -R $SUDO_USER:www-data /var/www/kinvoice.ng
chmod -R 755 /var/www/kinvoice.ng

echo ""
echo "========================================="
echo "Server setup completed successfully!"
echo "========================================="
echo ""
echo "Next steps:"
echo "1. Clone repository:"
echo "   cd /var/www"
echo "   git clone git@github.com:sudoname/khan-invoice.git kinvoice.ng"
echo ""
echo "2. Configure Nginx:"
echo "   cd /var/www/kinvoice.ng"
echo "   sudo cp nginx-kinvoice.conf /etc/nginx/sites-available/kinvoice.ng"
echo "   sudo ln -s /etc/nginx/sites-available/kinvoice.ng /etc/nginx/sites-enabled/"
echo "   sudo nginx -t && sudo systemctl reload nginx"
echo ""
echo "3. Run deployment script:"
echo "   cd /var/www/kinvoice.ng"
echo "   chmod +x deploy.sh"
echo "   sudo ./deploy.sh"
echo ""
echo "Database credentials:"
echo "  Database: khan_invoice"
echo "  Username: khan_invoice_user"
echo "  Password: KhanInv0ice#2025!Strong"
echo "========================================="
