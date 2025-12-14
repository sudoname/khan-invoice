# Seed Staging Database with Test Data

The staging database is currently empty, which causes:
- Empty customer list when creating invoices
- Dashboard shows 0 values (which is correct, but let's add test data)
- No invoices to view or test with

## Quick Fix: Add Test Data

SSH into your staging server and run these commands:

```bash
cd /var/www/staging.kinvoice.ng

# Create test customers for user ID 2 (abayomi.olorunsola@gmail.com)
php artisan tinker
```

Then paste this into the tinker console:

```php
$userId = 2; // Your Google Sign-In user ID

// Create 5 test customers
$customers = [
    ['name' => 'Acme Corporation', 'email' => 'contact@acme.com', 'phone' => '+234-801-234-5678', 'address' => '123 Business St, Lagos'],
    ['name' => 'Tech Solutions Ltd', 'email' => 'info@techsolutions.com', 'phone' => '+234-802-345-6789', 'address' => '456 Tech Avenue, Abuja'],
    ['name' => 'Global Enterprises', 'email' => 'sales@globalent.com', 'phone' => '+234-803-456-7890', 'address' => '789 Enterprise Rd, PH'],
    ['name' => 'First Bank Nigeria', 'email' => 'corporate@firstbank.ng', 'phone' => '+234-804-567-8901', 'address' => '321 Banking Plaza, Lagos'],
    ['name' => 'MTN Nigeria', 'email' => 'business@mtn.ng', 'phone' => '+234-805-678-9012', 'address' => '654 Telecom Tower, Lagos'],
];

foreach ($customers as $data) {
    App\Models\Customer::create([
        'user_id' => $userId,
        'name' => $data['name'],
        'email' => $data['email'],
        'phone' => $data['phone'],
        'address' => $data['address'],
    ]);
}

echo "Created " . App\Models\Customer::where('user_id', $userId)->count() . " customers\n";
exit
```

Now you have customers! The app should show them in the dropdown when creating invoices.

## Optional: Add Test Invoices

If you also want to add sample invoices for testing:

```bash
php artisan tinker
```

```php
$userId = 2;
$customers = App\Models\Customer::where('user_id', $userId)->get();

// Create 3 test invoices
foreach ($customers->take(3) as $index => $customer) {
    $invoice = App\Models\Invoice::create([
        'user_id' => $userId,
        'customer_id' => $customer->id,
        'invoice_number' => 'INV-2025-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
        'issue_date' => now()->subDays(rand(1, 30)),
        'due_date' => now()->addDays(rand(7, 30)),
        'sub_total' => 0,
        'tax_rate' => 7.5,
        'vat_amount' => 0,
        'discount_type' => 'percentage',
        'discount_value' => 0,
        'discount_total' => 0,
        'total_amount' => 0,
        'amount_paid' => 0,
        'notes' => 'Thank you for your business!',
        'terms' => 'Payment due within 30 days',
        'payment_status' => 'pending',
        'status' => 'sent',
        'is_public' => true,
        'currency' => '₦',
    ]);

    // Add 2 invoice items
    $items = [
        ['description' => 'Professional Services', 'quantity' => 1, 'unit_price' => 500000],
        ['description' => 'Consulting Fee', 'quantity' => 5, 'unit_price' => 50000],
    ];

    $subtotal = 0;
    foreach ($items as $itemData) {
        $lineTotal = $itemData['quantity'] * $itemData['unit_price'];
        App\Models\InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => $itemData['description'],
            'quantity' => $itemData['quantity'],
            'unit_price' => $itemData['unit_price'],
            'total' => $lineTotal,
        ]);
        $subtotal += $lineTotal;
    }

    // Update invoice totals
    $vatAmount = $subtotal * 0.075; // 7.5% VAT
    $invoice->update([
        'sub_total' => $subtotal,
        'vat_amount' => $vatAmount,
        'total_amount' => $subtotal + $vatAmount,
    ]);

    echo "Created invoice: {$invoice->invoice_number} - ₦" . number_format($invoice->total_amount, 2) . "\n";
}

echo "\nTotal invoices: " . App\Models\Invoice::where('user_id', $userId)->count() . "\n";
exit
```

## Verify the Data

Check that everything was created:

```bash
php artisan tinker --execute="echo 'Customers: ' . App\Models\Customer::count() . PHP_EOL;"
php artisan tinker --execute="echo 'Invoices: ' . App\Models\Invoice::count() . PHP_EOL;"
```

## Test the Mobile App

Now when you open the mobile app:

1. **Dashboard** - Should show:
   - Total Invoices: 3
   - Paid: 0
   - Pending: 3
   - Overdue: 0
   - Financial summary with proper amounts
   - Recent invoices list

2. **Create Invoice** - Should show:
   - Customer dropdown with 5 customers
   - All form fields working
   - Ability to create new invoices

3. **Invoice List** - Should show:
   - 3 existing invoices
   - Proper formatting and status colors
   - Share/View PDF options

## Alternative: Use Laravel Seeder

You can also create a proper seeder for reusable test data:

```bash
cd /var/www/staging.kinvoice.ng
php artisan make:seeder TestDataSeeder
```

Edit the seeder file and add the customer/invoice creation code, then run:

```bash
php artisan db:seed --class=TestDataSeeder
```

This way you can easily reset and reseed test data whenever needed!
