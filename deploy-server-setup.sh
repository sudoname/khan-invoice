#!/bin/bash

# Khan Invoice - Server Setup Script for Digital Ocean
# Domain: kinvoice.ng
# Server IP: 147.182.242.177

set -e  # Exit on error

echo "========================================="
echo "Khan Invoice - Server Setup"
echo "========================================="
echo ""

# Update system
echo "Step 1: Updating system packages..."
apt update && apt upgrade -y

# Install required packages
echo ""
echo "Step 2: Installing required packages..."
apt install -y software-properties-common curl wget git unzip

# Add PHP repository
echo ""
echo "Step 3: Adding PHP 8.3 repository..."
add-apt-repository ppa:ondrej/php -y
apt update

# Install PHP 8.3 and extensions
echo ""
echo "Step 4: Installing PHP 8.3 and extensions..."
apt install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-pgsql php8.3-sqlite3 \
    php8.3-curl php8.3-gd php8.3-mbstring php8.3-xml php8.3-zip php8.3-bcmath \
    php8.3-intl php8.3-redis php8.3-imagick php8.3-dom php8.3-fileinfo

# Install MySQL
echo ""
echo "Step 5: Installing MySQL Server..."
apt install -y mysql-server

# Install Nginx
echo ""
echo "Step 6: Installing Nginx..."
apt install -y nginx

# Install Node.js 20.x
echo ""
echo "Step 7: Installing Node.js 20.x..."
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

# Install Composer
echo ""
echo "Step 8: Installing Composer..."
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Certbot for SSL
echo ""
echo "Step 9: Installing Certbot for SSL certificates..."
apt install -y certbot python3-certbot-nginx

# Configure MySQL
echo ""
echo "Step 10: Securing MySQL installation..."
mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'temp_password_change_later';"
mysql -u root -ptemp_password_change_later <<EOF
CREATE DATABASE khan_invoice;
CREATE USER 'khan_invoice_user'@'localhost' IDENTIFIED BY 'KhanInv0ice#2025!Strong';
GRANT ALL PRIVILEGES ON khan_invoice.* TO 'khan_invoice_user'@'localhost';
FLUSH PRIVILEGES;
EOF

echo ""
echo "========================================="
echo "Server setup completed successfully!"
echo "========================================="
echo ""
echo "Next steps:"
echo "1. Clone the repository: git clone git@github.com:sudoname/khan-invoice.git /var/www/kinvoice.ng"
echo "2. Run the deployment script: bash /var/www/kinvoice.ng/deploy.sh"
echo ""
echo "Database credentials:"
echo "  Database: khan_invoice"
echo "  Username: khan_invoice_user"
echo "  Password: KhanInv0ice#2025!Strong"
echo ""
echo "IMPORTANT: Change the MySQL root password!"
echo "  Current root password: temp_password_change_later"
echo "========================================="
