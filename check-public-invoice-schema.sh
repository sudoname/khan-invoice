#!/bin/bash

# Check Public Invoice Schema on Staging

cd /var/www/staging.kinvoice.ng

echo "Checking public_invoices table schema..."
echo ""

php artisan tinker --execute="
\$columns = DB::select('DESCRIBE public_invoices');
foreach (\$columns as \$col) {
    echo \$col->Field . ' | ' . \$col->Type . ' | ' . \$col->Null . ' | ' . \$col->Key . ' | ' . \$col->Default . PHP_EOL;
}
"

echo ""
echo "Checking if business_profile_id exists..."
php artisan tinker --execute="
\$hasColumn = DB::getSchemaBuilder()->hasColumn('public_invoices', 'business_profile_id');
echo 'business_profile_id exists: ' . (\$hasColumn ? 'YES' : 'NO') . PHP_EOL;
"
