# Fee Structure Changes - Business Absorbs Fees

## Summary of Changes

**Previous Model:** Customer pays invoice amount + fees (Paystack fee + Service charge)
**New Model:** Customer pays ONLY invoice amount, Business absorbs all fees

---

## What Changed

### 1. PaymentSetting Model (`app/Models/PaymentSetting.php`)

**Added Method:** `calculateNetAmountReceived()`

```php
public static function calculateNetAmountReceived(float $invoiceAmount): array
{
    $paystackFee = self::calculatePaystackFee($invoiceAmount);
    $serviceCharge = self::calculateServiceCharge($invoiceAmount);
    $totalFees = $paystackFee + $serviceCharge;
    $netAmount = $invoiceAmount - $totalFees;

    return [
        'invoice_amount' => $invoiceAmount,
        'paystack_fee' => $paystackFee,
        'service_charge' => $serviceCharge,
        'total_fees' => $totalFees,
        'net_amount_received' => max(0, $netAmount),
        'customer_pays' => $invoiceAmount,
    ];
}
```

**Purpose:** Calculates fee breakdown and net amount business owner receives.

**Money Flow (Paystack Automatic Split):**
```
Customer pays ₦10,000 → Paystack

Paystack automatically distributes:
├─ Paystack Fee: ₦250 → Paystack keeps
├─ Service Charge: ₦200 → Main Account (kinvoice.ng platform)
└─ Net Amount: ₦9,550 → Business Owner's Subaccount
```

---

### 2. Public Invoice View (`resources/views/public-invoice/show.blade.php`)

**Changes Made:**

#### Payment Modal Display
- **Removed:** Fee breakdown showing Paystack fee and Service charge
- **Now Shows:** Only invoice amount
- **Added Message:** "Processing fees are absorbed by the merchant"

**Before:**
```
Invoice Amount: ₦10,000.00
Paystack Processing Fee: ₦250.00
Service Charge: ₦200.00
Total to Pay: ₦10,450.00
```

**After:**
```
Invoice Amount: ₦10,000.00
Total to Pay: ₦10,000.00
Processing fees are absorbed by the merchant
```

#### Paystack Payment Initialization
- **Changed:** `amount` parameter now sends only invoice amount (not invoice + fees)
- **Updated:** `transaction_charge` now sends service charge to platform's main account
- **Updated:** `bearer: 'account'` means subaccount (business) bears the charge
- **Updated:** Metadata to include net calculation details

**Before:**
```javascript
amount: Math.round(totalWithFees * 100), // ₦10,450 in kobo
transaction_charge: Math.round(totalFees * 100), // All fees
bearer: 'account', // Customer pays all fees
```

**After:**
```javascript
amount: Math.round(invoiceAmount * 100), // ₦10,000 in kobo (customer pays only invoice)
transaction_charge: Math.round(serviceCharge * 100), // ₦200 in kobo (platform's fee)
bearer: 'account', // Subaccount (business) bears the charge
```

**How Paystack Split Works:**
1. Customer pays ₦10,000 to Paystack
2. Paystack deducts their fee: ₦10,000 - ₦250 = ₦9,750
3. From the ₦9,750, Paystack splits:
   - **₦200** → Main account (transaction_charge - platform's service charge)
   - **₦9,550** → Subaccount (business owner's net amount)

---

### 3. Webhook Handler (`app/Http/Controllers/PublicInvoiceController.php`)

**Enhanced Logging:**

The webhook now logs detailed fee breakdown:
- Customer paid amount
- Paystack fee deducted
- Service charge deducted
- Total fees deducted
- **Net amount business receives**

```php
Log::info('Public invoice payment processed', [
    'customer_paid' => 10000.00,
    'paystack_fee_deducted' => 250.00,
    'service_charge_deducted' => 200.00,
    'total_fees_deducted' => 450.00,
    'net_amount_business_receives' => 9750.00,
    'fee_model' => 'business_absorbs_fees',
]);
```

---

## Example Calculations

### Scenario 1: ₦10,000 Invoice

**Customer Experience:**
- Views invoice: ₦10,000
- Payment modal shows: ₦10,000
- Pays to Paystack: ₦10,000

**Business Owner Receives:**
```
Invoice Amount: ₦10,000.00
- Paystack Fee (1.5% + ₦100): -₦250.00
- Service Charge (2%, min ₦150): -₦200.00
= Net Amount Received: ₦9,750.00
```

### Scenario 2: ₦50,000 Invoice

**Customer Experience:**
- Views invoice: ₦50,000
- Payment modal shows: ₦50,000
- Pays to Paystack: ₦50,000

**Business Owner Receives:**
```
Invoice Amount: ₦50,000.00
- Paystack Fee (1.5% + ₦100): -₦850.00
- Service Charge (2%, min ₦150): -₦1,000.00
= Net Amount Received: ₦48,150.00
```

### Scenario 3: ₦200,000 Invoice

**Customer Experience:**
- Views invoice: ₦200,000
- Payment modal shows: ₦200,000
- Pays to Paystack: ₦200,000

**Business Owner Receives:**
```
Invoice Amount: ₦200,000.00
- Paystack Fee (1.5% + ₦100, capped at ₦3,000): -₦3,000.00
- Service Charge (2%, capped at ₦3,000): -₦3,000.00
= Net Amount Received: ₦194,000.00
```

---

## Testing Instructions

### Test 1: View Payment Modal

1. **Create Public Invoice:**
   - Go to: `http://localhost:9000/public-invoice/create`
   - Fill in invoice details with amount: ₦10,000
   - Generate invoice

2. **Check Payment Modal:**
   - Click "Pay Now" button
   - **Verify:** Only shows invoice amount (₦10,000)
   - **Verify:** No fee breakdown displayed
   - **Verify:** Message says "Processing fees are absorbed by the merchant"
   - **Verify:** Total to Pay = Invoice Amount

### Test 2: Browser Console Check

1. **Open Payment Modal**
2. **Open Browser Console** (F12 → Console tab)
3. **Submit Payment Form** (don't complete payment, just trigger it)
4. **Check Paystack Initialization:**
   ```javascript
   // Look for PaystackPop.setup() call
   // Verify: amount = 1000000 (₦10,000 in kobo)
   // Verify: NO transaction_charge parameter
   // Verify: NO bearer parameter
   ```

### Test 3: Complete Test Payment (Staging/Test Environment)

**Prerequisites:**
- Use Paystack TEST keys
- Use test card: `4084084084084081`

**Steps:**
1. Create invoice for ₦10,000
2. Click "Pay Now"
3. Fill in payer details
4. Complete payment with test card
5. **Check webhook logs:**
   ```bash
   cd /var/www/khan-invoice
   tail -f storage/logs/laravel.log | grep "Public invoice payment processed"
   ```

6. **Verify Log Output:**
   ```
   customer_paid: 10000.00
   paystack_fee_deducted: 250.00
   service_charge_deducted: 200.00
   total_fees_deducted: 450.00
   net_amount_business_receives: 9750.00
   fee_model: business_absorbs_fees
   ```

### Test 4: Check Database

```bash
php artisan tinker

# Find the invoice
$invoice = App\Models\PublicInvoice::latest()->first();

# Check payment status
$invoice->payment_status; // Should be: 'paid'
$invoice->amount_paid; // Should be: 10000.00 (customer paid amount)
$invoice->paid_at; // Should show payment timestamp
```

---

## Fee Configuration

Current fee settings can be viewed/changed in:
- **Admin Panel:** `http://localhost:9000/admin/payment-settings`
- **Database:** `payment_settings` table

**Current Defaults:**
```
Paystack Fee: 1.5% + ₦100, capped at ₦3,000
Service Charge: 2%, minimum ₦150, capped at ₦3,000
```

---

## Impact Analysis

### Customer Benefits
✅ Pay only the invoice amount (simpler, clearer)
✅ No surprise fees at checkout
✅ Better user experience

### Business Owner Impact
⚠️ Fees are now deducted from revenue
⚠️ Need to account for fees in pricing
⚠️ Net revenue is lower per transaction

### Example Impact:
```
Old Model:
- Invoice: ₦10,000
- Customer pays: ₦10,450
- Business receives: ₦10,000
- Fees paid by: Customer

New Model:
- Invoice: ₦10,000
- Customer pays: ₦10,000
- Business receives: ₦9,750
- Fees paid by: Business
```

---

## Rollback Plan

If you need to revert to the old fee model (customer pays fees):

### 1. Restore Old PaymentSetting Method

```php
// In public-invoice/show.blade.php, change back to:
$fees = \App\Models\PaymentSetting::calculateTotalFees($invoiceAmount);
```

### 2. Restore Fee Display in Payment Modal

```blade
<div class="flex justify-between text-sm text-blue-600">
    <span>Paystack Processing Fee</span>
    <span>₦{{ number_format($fees['paystack_fee'], 2) }}</span>
</div>
<div class="flex justify-between text-sm text-purple-600">
    <span>Service Charge</span>
    <span>₦{{ number_format($fees['service_charge'], 2) }}</span>
</div>
```

### 3. Restore Paystack Parameters

```javascript
amount: Math.round(totalWithFees * 100),
transaction_charge: Math.round(totalFees * 100),
bearer: 'account',
```

---

## Important Notes

### Paystack Subaccounts & Automatic Split
- When using subaccounts, Paystack automatically splits payments
- **WITH** `transaction_charge` parameter, Paystack sends that amount to main account
- Business owner's subaccount receives: (Customer Payment - Paystack Fee - transaction_charge)
- **Service charge is automatically collected** by platform through transaction_charge

### Service Charge Handling (Automated)
✅ Service charge is automatically sent to platform's main account via Paystack split
✅ Business owner receives net amount directly in their subaccount
✅ No manual deductions needed - Paystack handles everything
✅ Full audit trail in webhook logs

**Example Flow:**
```
Customer pays: ₦10,000
    ↓
Paystack processes payment
    ↓
Paystack deducts their fee: ₦250
    ↓
Remaining: ₦9,750
    ↓
    ├─ Main Account (Platform): ₦200 (service charge via transaction_charge)
    └─ Subaccount (Business): ₦9,550 (net amount)
```

### Production Deployment

**Before deploying to production:**
1. ✅ Test thoroughly on staging
2. ✅ Verify fee calculations with various invoice amounts
3. ✅ Test webhook logging
4. ✅ Inform business owners about fee model change
5. ✅ Update invoice pricing to account for absorbed fees
6. ⚠️ Consider adding fee information to business owner dashboard

---

## Files Modified

```
✅ app/Models/PaymentSetting.php
✅ resources/views/public-invoice/show.blade.php
✅ app/Http/Controllers/PublicInvoiceController.php
✅ FEE_STRUCTURE_CHANGES.md (this file)
```

---

## Support

If you encounter issues:
1. Check `storage/logs/laravel.log` for detailed logging
2. Verify Paystack test keys are configured correctly
3. Test with small amounts first (₦100 - ₦1,000)
4. Check browser console for JavaScript errors

---

**Status:** ✅ Implementation Complete
**Date:** December 13, 2025
**Fee Model:** Business Absorbs Fees
