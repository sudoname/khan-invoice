#!/bin/bash

# Khan Invoice - Copy Production DB to Staging
# Safely copies production database to staging with backup
# Reads credentials from .env files

set -e

echo "========================================="
echo "Khan Invoice - DB Copy: Prod → Staging"
echo "========================================="
echo ""

# Paths
PROD_PATH="/var/www/kinvoice.ng"
STAGING_PATH="/var/www/staging.kinvoice.ng"
BACKUP_DIR="/var/backups/khan-invoice"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Helper function to read .env values
get_env_value() {
    local env_file=$1
    local key=$2
    grep "^${key}=" "$env_file" | cut -d '=' -f2- | tr -d '"' | tr -d "'"
}

echo "Step 1: Reading database credentials from .env files..."

# Read production database credentials
if [ ! -f "$PROD_PATH/.env" ]; then
    echo "❌ Production .env file not found: $PROD_PATH/.env"
    exit 1
fi

PROD_DB_NAME=$(get_env_value "$PROD_PATH/.env" "DB_DATABASE")
PROD_DB_USER=$(get_env_value "$PROD_PATH/.env" "DB_USERNAME")
PROD_DB_PASS=$(get_env_value "$PROD_PATH/.env" "DB_PASSWORD")

echo "✓ Production database: $PROD_DB_NAME (user: $PROD_DB_USER)"

# Read staging database credentials
if [ ! -f "$STAGING_PATH/.env" ]; then
    echo "❌ Staging .env file not found: $STAGING_PATH/.env"
    exit 1
fi

STAGING_DB_NAME=$(get_env_value "$STAGING_PATH/.env" "DB_DATABASE")
STAGING_DB_USER=$(get_env_value "$STAGING_PATH/.env" "DB_USERNAME")
STAGING_DB_PASS=$(get_env_value "$STAGING_PATH/.env" "DB_PASSWORD")

echo "✓ Staging database: $STAGING_DB_NAME (user: $STAGING_DB_USER)"

echo ""
echo "⚠️  WARNING: This will replace staging database with production data!"
echo ""
echo "Production DB: $PROD_DB_NAME (from $PROD_PATH/.env)"
echo "Staging DB: $STAGING_DB_NAME (from $STAGING_PATH/.env)"
echo ""
read -p "Are you sure you want to continue? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo "❌ Operation cancelled."
    exit 1
fi

echo ""
echo "Step 1: Creating backup directory..."
mkdir -p $BACKUP_DIR
echo "✓ Backup directory ready: $BACKUP_DIR"

echo ""
echo "Step 2: Backing up PRODUCTION database..."
mysqldump -u "$PROD_DB_USER" --password="$PROD_DB_PASS" "$PROD_DB_NAME" | gzip > $BACKUP_DIR/production_backup_$TIMESTAMP.sql.gz

if [ $? -eq 0 ]; then
    echo "✓ Production backup created: production_backup_$TIMESTAMP.sql.gz"
    PROD_BACKUP_SIZE=$(du -h $BACKUP_DIR/production_backup_$TIMESTAMP.sql.gz | cut -f1)
    echo "  Size: $PROD_BACKUP_SIZE"
else
    echo "❌ Production backup FAILED! Aborting."
    exit 1
fi

echo ""
echo "Step 3: Backing up STAGING database..."
mysqldump -u "$STAGING_DB_USER" --password="$STAGING_DB_PASS" "$STAGING_DB_NAME" | gzip > $BACKUP_DIR/staging_backup_$TIMESTAMP.sql.gz

if [ $? -eq 0 ]; then
    echo "✓ Staging backup created: staging_backup_$TIMESTAMP.sql.gz"
    STAGING_BACKUP_SIZE=$(du -h $BACKUP_DIR/staging_backup_$TIMESTAMP.sql.gz | cut -f1)
    echo "  Size: $STAGING_BACKUP_SIZE"
else
    echo "❌ Staging backup FAILED! Aborting."
    exit 1
fi

echo ""
echo "Step 4: Creating production dump for import..."
# Use the production backup we just created
PROD_DUMP_FILE="$BACKUP_DIR/production_backup_$TIMESTAMP.sql.gz"
echo "✓ Using production backup for import"

echo ""
echo "Step 5: Dropping existing STAGING tables..."
mysql -u "$STAGING_DB_USER" --password="$STAGING_DB_PASS" "$STAGING_DB_NAME" -e "SET FOREIGN_KEY_CHECKS = 0;
    SELECT CONCAT('DROP TABLE IF EXISTS \`', table_name, '\`;')
    FROM information_schema.tables
    WHERE table_schema = '$STAGING_DB_NAME';" \
    | grep DROP | mysql -u "$STAGING_DB_USER" --password="$STAGING_DB_PASS" "$STAGING_DB_NAME"

echo "✓ Staging tables dropped"

echo ""
echo "Step 6: Importing PRODUCTION data to STAGING..."
gunzip < $PROD_DUMP_FILE | mysql -u "$STAGING_DB_USER" --password="$STAGING_DB_PASS" "$STAGING_DB_NAME"

if [ $? -eq 0 ]; then
    echo "✓ Production data imported to staging"
else
    echo "❌ Import FAILED! Restoring staging backup..."
    gunzip < $BACKUP_DIR/staging_backup_$TIMESTAMP.sql.gz | mysql -u "$STAGING_DB_USER" --password="$STAGING_DB_PASS" "$STAGING_DB_NAME"
    echo "✓ Staging restored from backup"
    exit 1
fi

echo ""
echo "Step 7: Updating staging environment-specific data..."
cd "$STAGING_PATH"

# Update APP_ENV to staging
php artisan tinker --execute="
    DB::table('users')->update(['email_verified_at' => now()]);
    echo 'Email verification timestamps updated\n';
"

echo "✓ Environment data updated"

echo ""
echo "Step 8: Running migrations (if any new ones)..."
php artisan migrate --force

echo ""
echo "Step 9: Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan optimize

echo ""
echo "========================================="
echo "✅ Database Copy Completed Successfully!"
echo "========================================="
echo ""
echo "Backups Created (Both databases backed up!):"
echo "  📁 Production backup: $BACKUP_DIR/production_backup_$TIMESTAMP.sql.gz ($PROD_BACKUP_SIZE)"
echo "  📁 Staging backup: $BACKUP_DIR/staging_backup_$TIMESTAMP.sql.gz ($STAGING_BACKUP_SIZE)"
echo ""
echo "Staging database now contains production data from:"
echo "  🗄️  Database: $PROD_DB_NAME"
echo "  ⏰ Timestamp: $(date)"
echo ""
echo "Important Notes:"
echo "  1. ✅ BOTH production and staging databases backed up before copy"
echo "  2. All users' emails are verified for testing"
echo "  3. Payment webhooks will still point to production URLs"
echo "  4. Update .env if needed for staging-specific configs"
echo ""
echo "To restore from backups if needed:"
echo ""
echo "  Restore staging:"
echo "    gunzip < $BACKUP_DIR/staging_backup_$TIMESTAMP.sql.gz | mysql -u $STAGING_DB_USER -p $STAGING_DB_NAME"
echo ""
echo "  Restore production (if needed):"
echo "    gunzip < $BACKUP_DIR/production_backup_$TIMESTAMP.sql.gz | mysql -u $PROD_DB_USER -p $PROD_DB_NAME"
echo ""
echo "========================================="
