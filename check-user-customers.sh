#!/bin/bash

# Check User and Customer Data
# Run this on staging server

cd /var/www/staging.kinvoice.ng

echo "=== Checking Users and Customers ==="
echo ""

php artisan tinker --execute="
// Find all users with 'admin@khaninvoice.com'
\$users = App\Models\User::where('email', 'admin@khaninvoice.com')->get();
echo 'Users found: ' . \$users->count() . PHP_EOL;
echo '' . PHP_EOL;

foreach (\$users as \$user) {
    echo 'User #' . \$user->id . PHP_EOL;
    echo '  Email: ' . \$user->email . PHP_EOL;
    echo '  Role: ' . \$user->role . PHP_EOL;
    echo '  Created: ' . \$user->created_at . PHP_EOL;
    
    \$customerCount = App\Models\Customer::where('user_id', \$user->id)->count();
    echo '  Customers: ' . \$customerCount . PHP_EOL;
    
    if (\$customerCount > 0) {
        \$customers = App\Models\Customer::where('user_id', \$user->id)->limit(3)->get();
        foreach (\$customers as \$c) {
            echo '    - ' . \$c->name . ' (ID: ' . \$c->id . ')' . PHP_EOL;
        }
    }
    echo '' . PHP_EOL;
}

// Show ALL users
echo 'All users in database:' . PHP_EOL;
\$allUsers = App\Models\User::all();
foreach (\$allUsers as \$u) {
    \$custCount = App\Models\Customer::where('user_id', \$u->id)->count();
    echo '  User #' . \$u->id . ': ' . \$u->email . ' (Role: ' . \$u->role . ', Customers: ' . \$custCount . ')' . PHP_EOL;
}
"
