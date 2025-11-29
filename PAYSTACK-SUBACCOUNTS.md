# Paystack Subaccounts - Multi-Tenant Payment Setup

## Overview

Khan Invoice now supports **Paystack Subaccounts**, allowing each business to receive invoice payments directly into their own bank account. This enables true multi-tenant payment processing where:

- Each business profile has its own Paystack subaccount
- Customer payments go directly to the business's bank account
- Platform can optionally charge a service fee (percentage split)
- Automatic settlement to business accounts

## How It Works

```
Customer pays invoice
    ↓
Paystack processes payment
    ↓
Money goes to Business Bank Account (via subaccount)
    ↓
(Optional) Platform takes percentage fee
```

## Setup Process

### 1. Configure Paystack API Keys

Ensure your main Paystack account API keys are configured in `.env`:

```env
PAYSTACK_PUBLIC_KEY=pk_live_xxxxxxxxxxxxx
PAYSTACK_SECRET_KEY=sk_live_xxxxxxxxxxxxx
```

### 2. Set Up Business Bank Account

When creating a Business Profile, ensure these fields are filled:

- **Bank Name**: e.g., "GTBank", "Access Bank", "Zenith Bank"
- **Account Number**: 10-digit account number
- **Account Name**: Account holder name

These are already in the Business Profile form.

### 3. Create Paystack Subaccount

You'll need to create a Paystack subaccount for each business. This can be done via:

#### Option A: Artisan Command (Recommended)

Create an artisan command to automate subaccount creation:

```bash
php artisan paystack:create-subaccount {business_profile_id}
```

Example command implementation needed (see below).

#### Option B: Manual API Call

Use the `PaystackService` directly:

```php
use App\Services\PaystackService;
use App\Models\BusinessProfile;

$paystackService = new PaystackService();
$business = BusinessProfile::find($id);

// Get bank code from bank name
$bankCode = $paystackService->getBankCode($business->bank_name);

// Verify account number
$accountVerification = $paystackService->resolveAccountNumber(
    $business->bank_account_number,
    $bankCode
);

if ($accountVerification['status']) {
    // Create subaccount
    $result = $paystackService->createSubaccount([
        'business_name' => $business->business_name,
        'settlement_bank' => $bankCode,
        'account_number' => $business->bank_account_number,
        'percentage_charge' => 2.5, // 2.5% platform fee (optional)
        'description' => 'Subaccount for ' . $business->business_name,
        'primary_contact_email' => $business->email,
        'primary_contact_name' => $business->business_name,
        'primary_contact_phone' => $business->phone,
    ]);

    if ($result['status']) {
        // Save subaccount details
        $business->update([
            'paystack_subaccount_id' => $result['data']['id'],
            'paystack_subaccount_code' => $result['data']['subaccount_code'],
            'paystack_settlement_bank' => $bankCode,
            'paystack_split_percentage' => 2.5,
        ]);
    }
}
```

### 4. Test Payment Flow

1. Create an invoice for that business
2. Share invoice with customer
3. Customer clicks "Pay Now" button
4. Payment routes to business's bank account automatically
5. Business receives settlement from Paystack

## Database Fields

New fields in `business_profiles` table:

| Field | Type | Description |
|-------|------|-------------|
| `paystack_subaccount_id` | string | Paystack subaccount ID |
| `paystack_subaccount_code` | string | Subaccount code (used in transactions) |
| `paystack_settlement_bank` | string | Bank code for settlements |
| `paystack_split_percentage` | decimal | Platform fee percentage (0-100) |

## PaystackService Methods

### Create Subaccount
```php
$paystackService->createSubaccount([
    'business_name' => 'Business Name',
    'settlement_bank' => '058', // Bank code
    'account_number' => '0123456789',
    'percentage_charge' => 2.5, // Optional platform fee
    'description' => 'Subaccount description',
    'primary_contact_email' => 'contact@business.com',
]);
```

### List Banks
```php
$banks = $paystackService->listBanks();
// Returns array of Nigerian banks with codes
```

### Resolve Account Number
```php
$result = $paystackService->resolveAccountNumber(
    '0123456789',
    '058' // GTBank code
);
// Returns account name and bank details
```

### Get Bank Code
```php
$code = $paystackService->getBankCode('GTBank');
// Returns '058'
```

## Platform Fee Configuration

The `paystack_split_percentage` field controls how much the platform charges:

- **0%**: No platform fee, 100% goes to business
- **2.5%**: Platform keeps 2.5%, business gets 97.5%
- **5%**: Platform keeps 5%, business gets 95%

Set this when creating the subaccount.

## Artisan Command (Recommended Implementation)

Create: `app/Console/Commands/CreatePaystackSubaccount.php`

```php
<?php

namespace App\Console\Commands;

use App\Models\BusinessProfile;
use App\Services\PaystackService;
use Illuminate\Console\Command;

class CreatePaystackSubaccount extends Command
{
    protected $signature = 'paystack:create-subaccount {business_profile_id} {--fee=2.5}';
    protected $description = 'Create Paystack subaccount for a business profile';

    public function handle()
    {
        $businessId = $this->argument('business_profile_id');
        $platformFee = $this->option('fee');

        $business = BusinessProfile::find($businessId);

        if (!$business) {
            $this->error('Business profile not found!');
            return 1;
        }

        if ($business->paystack_subaccount_code) {
            $this->warn('Business already has a subaccount: ' . $business->paystack_subaccount_code);
            return 0;
        }

        $this->info("Creating subaccount for: {$business->business_name}");

        $paystackService = new PaystackService();

        // Get bank code
        $this->info('Looking up bank code...');
        $bankCode = $paystackService->getBankCode($business->bank_name);

        if (!$bankCode) {
            $this->error("Could not find bank code for: {$business->bank_name}");
            return 1;
        }

        // Verify account
        $this->info('Verifying account number...');
        $verification = $paystackService->resolveAccountNumber(
            $business->bank_account_number,
            $bankCode
        );

        if (!$verification['status']) {
            $this->error('Account verification failed: ' . $verification['message']);
            return 1;
        }

        $this->info('Account verified: ' . $verification['data']['account_name']);

        // Create subaccount
        $this->info('Creating Paystack subaccount...');
        $result = $paystackService->createSubaccount([
            'business_name' => $business->business_name,
            'settlement_bank' => $bankCode,
            'account_number' => $business->bank_account_number,
            'percentage_charge' => $platformFee,
            'description' => 'Khan Invoice subaccount for ' . $business->business_name,
            'primary_contact_email' => $business->email,
            'primary_contact_name' => $business->business_name,
            'primary_contact_phone' => $business->phone,
        ]);

        if (!$result['status']) {
            $this->error('Subaccount creation failed: ' . $result['message']);
            return 1;
        }

        // Save subaccount details
        $business->update([
            'paystack_subaccount_id' => $result['data']['id'],
            'paystack_subaccount_code' => $result['data']['subaccount_code'],
            'paystack_settlement_bank' => $bankCode,
            'paystack_split_percentage' => $platformFee,
        ]);

        $this->info('✓ Subaccount created successfully!');
        $this->info('Subaccount Code: ' . $result['data']['subaccount_code']);
        $this->info('Platform Fee: ' . $platformFee . '%');

        return 0;
    }
}
```

Usage:
```bash
# Create subaccount with 2.5% platform fee
php artisan paystack:create-subaccount 1

# Create subaccount with 5% platform fee
php artisan paystack:create-subaccount 1 --fee=5

# Create subaccount with 0% platform fee (no fees)
php artisan paystack:create-subaccount 1 --fee=0
```

## Important Notes

1. **Bank Account Verification**: Always verify account numbers before creating subaccounts
2. **One Subaccount Per Business**: Each business should have only one subaccount
3. **Settlement**: Paystack settles funds according to your account settings (usually T+1 or T+2 days)
4. **Platform Fees**: Fees are automatically deducted by Paystack during settlement
5. **Testing**: Use Paystack test keys for development/testing

## Common Bank Codes

| Bank | Code |
|------|------|
| Access Bank | 044 |
| GTBank | 058 |
| Zenith Bank | 057 |
| First Bank | 011 |
| UBA | 033 |
| Ecobank | 050 |
| FCMB | 214 |
| Stanbic IBTC | 221 |
| Sterling Bank | 232 |
| Wema Bank | 035 |

Use `php artisan paystack:list-banks` to get full list (command implementation needed).

## Troubleshooting

### Subaccount Creation Fails
- Verify bank account details are correct
- Ensure Paystack API keys are configured
- Check account number belongs to the specified bank
- Verify bank name matches Paystack's bank list

### Payments Not Routing to Subaccount
- Check `paystack_subaccount_code` is set in business profile
- Verify subaccount is active on Paystack dashboard
- Check Paystack webhook logs for errors

### Account Verification Fails
- Double-check account number (10 digits)
- Verify bank code is correct
- Ensure account is active and not dormant

## Support

For Paystack support:
- Dashboard: https://dashboard.paystack.com
- Documentation: https://paystack.com/docs/payments/split-payments
- API Reference: https://paystack.com/docs/api/subaccount

---

**Next Steps:**
1. Create the artisan command for easy subaccount creation
2. Add subaccount management to admin panel
3. Test payment flow with real transactions
4. Monitor settlements in Paystack dashboard
