# Payment Integrity Guide - Preventing Orphaned Payments

## The Problem

**Orphaned Invoice Payments** occur when an invoice is marked as "paid" in the database but has no corresponding:
- `invoice_payments` record
- `payment_attempts` record
- `ledger_entries` records
- Merchant balance is NOT updated

**Example:** Invoice INV-2026-0003 was marked as paid (₦10,750) but money never appeared in merchant account.

---

## How It Happens

### ❌ Common Causes:

1. **Manual Database Updates**
   ```sql
   -- NEVER DO THIS:
   UPDATE invoices SET payment_status='completed', amount_paid=10750 WHERE id=56;
   ```
   This bypasses all business logic, ledger entries, and balance updates.

2. **Incomplete Webhook Processing**
   - Paystack webhook arrives
   - Invoice gets marked as paid
   - But payment record creation fails/crashes
   - No rollback, invoice stays "paid" with no records

3. **Failed Transactions Without Rollback**
   - Payment verification succeeds
   - Database transaction starts
   - Crashes before commit
   - Invoice status persists but records don't

4. **Direct API Calls to Database**
   - Using raw queries instead of PaymentService
   - Skips ledger entry creation
   - Skips platform service charge deduction

---

## Prevention Measures

### ✅ 1. Always Use PaymentService

**CORRECT Way to Record a Payment:**
```php
use App\Services\Payment\PaymentService;

$paymentService = app(PaymentService::class);
$result = $paymentService->verifyPayment($reference);

// This automatically:
// - Creates PaymentAttempt record
// - Creates InvoicePayment record
// - Creates 3 ledger entries (PAYMENT_RECEIVED, GATEWAY_FEE, PLATFORM_FEE)
// - Updates merchant balance
// - Updates invoice status
```

**WRONG Way (Never Do This):**
```php
// ❌ DON'T manually update invoice
$invoice->update([
    'payment_status' => 'completed',
    'amount_paid' => 10750,
]);
```

### ✅ 2. Use Database Transactions

All payment operations should be wrapped in transactions:
```php
DB::beginTransaction();
try {
    // Create payment records
    // Create ledger entries
    // Update balances
    DB::commit();
} catch (Exception $e) {
    DB::rollBack();
    Log::error('Payment failed', ['error' => $e->getMessage()]);
    throw $e;
}
```

### ✅ 3. Regular Monitoring

**Daily Check for Orphaned Invoices:**
```bash
php artisan payments:detect-orphaned-invoices
```

This has been added to your scheduler in `bootstrap/app.php`:
```php
// Runs daily at 2:00 AM
$schedule->command('payments:detect-orphaned-invoices')
    ->dailyAt('02:00')
    ->emailOutputOnFailure('yomi@khan.ng');
```

### ✅ 4. Webhook Validation

Ensure PaystackWebhookController properly handles failures:
```php
public function handleChargeSuccess(array $data): void
{
    DB::beginTransaction();
    try {
        $result = $this->paymentService->verifyPayment($data['reference']);

        if (!$result['success']) {
            Log::error('Webhook payment verification failed', $data);
            DB::rollBack();
            return;
        }

        DB::commit();
    } catch (Exception $e) {
        DB::rollBack();
        Log::error('Webhook processing failed', [
            'reference' => $data['reference'],
            'error' => $e->getMessage(),
        ]);
        throw $e; // Re-throw so Paystack will retry
    }
}
```

### ✅ 5. Manual Payment Backfill Process

If you discover an orphaned payment, use this process:

```bash
# 1. Verify the payment was actually received
# Check Paystack dashboard for the transaction

# 2. Create a backfill script (example provided in codebase)
php artisan make:command BackfillPayment

# 3. The script should:
# - Create PaymentAttempt record
# - Create InvoicePayment record
# - Calculate platform fees correctly
# - Create 3 ledger entries (PAYMENT_RECEIVED, GATEWAY_FEE, PLATFORM_FEE)
# - Update merchant balance
# - All within a DB transaction

# 4. Run the backfill
php backfill_payment_script.php

# 5. Verify the fix
php artisan payments:diagnose-user-balance user@email.com
```

---

## Current Payment Flow (Correct)

```
1. Customer pays invoice
   ↓
2. Paystack processes payment
   ↓
3. Paystack sends webhook to our system
   ↓
4. PaymentService::verifyPayment($reference)
   ↓
5. Creates PaymentAttempt record
   ↓
6. Creates InvoicePayment record
   ↓
7. Calculates fees:
   - Paystack fee: 1.5% + ₦100 (capped at ₦2,000)
   - Platform service charge: 2% (min ₦200, cap ₦3,000)
   ↓
8. Creates 3 Ledger Entries:
   - PAYMENT_RECEIVED (CREDIT) - Merchant's net amount
   - GATEWAY_FEE (DEBIT) - Paystack fee (info only)
   - PLATFORM_FEE (DEBIT) - Platform charge (info only)
   ↓
9. Updates invoice:
   - payment_status = 'completed'
   - amount_paid = total amount
   - paid_at = now()
   ↓
10. Merchant balance reflects net amount immediately
```

---

## Fee Structure

### Customer Pays: ₦10,000

| Item | Amount | Formula |
|------|--------|---------|
| **Customer Pays** | ₦10,000.00 | Invoice amount |
| Paystack Fee | -₦250.00 | (1.5% + ₦100) capped at ₦2,000 |
| Net After Paystack | ₦9,750.00 | |
| Platform Service Charge | -₦200.00 | 2% (min ₦200, max ₦3,000) |
| **Merchant Receives** | **₦9,550.00** | Final balance credit |

---

## Monitoring & Alerts

### Key Metrics to Monitor:

1. **Orphaned Invoices Count**
   - Should always be 0
   - Alert if > 0

2. **Payment Success Rate**
   - Track successful vs failed payments
   - Alert if success rate drops below 95%

3. **Ledger Entry Integrity**
   - Every PAYMENT_RECEIVED should have matching GATEWAY_FEE and PLATFORM_FEE
   - Every InvoicePayment should have exactly 3 ledger entries

4. **Balance Reconciliation**
   - Sum of all CREDIT entries - sum of all DEBIT entries = Current Balance
   - Run weekly reconciliation report

### Sample Monitoring Queries:

```sql
-- Find invoices marked paid with no payment records
SELECT i.id, i.invoice_number, i.amount_paid, i.payment_status
FROM invoices i
LEFT JOIN invoice_payments ip ON i.id = ip.invoice_id
WHERE i.payment_status IN ('completed', 'processing')
AND i.amount_paid > 0
AND ip.id IS NULL;

-- Find invoice_payments with missing ledger entries
SELECT ip.id, ip.payment_reference, COUNT(le.id) as ledger_count
FROM invoice_payments ip
LEFT JOIN ledger_entries le ON ip.id = le.invoice_payment_id
GROUP BY ip.id, ip.payment_reference
HAVING ledger_count != 3;
```

---

## Emergency Response

If you discover an orphaned payment:

1. **Immediate Actions:**
   - Document the invoice number and amount
   - Check Paystack dashboard to verify payment was received
   - Check if customer was actually charged

2. **Investigation:**
   - Review logs around the payment timestamp
   - Check for any error messages
   - Identify what caused the bypass

3. **Resolution:**
   - Run the backfill script (provided in codebase)
   - Verify merchant balance updated correctly
   - Verify all 3 ledger entries created
   - Notify merchant if needed

4. **Prevention:**
   - Fix the root cause (webhook, transaction handling, etc.)
   - Add monitoring for this scenario
   - Document the incident

---

## Best Practices

✅ **DO:**
- Always use PaymentService for payment operations
- Wrap all payment logic in database transactions
- Use proper error handling and logging
- Run daily orphaned invoice checks
- Keep Paystack webhook endpoint reliable
- Test payment flows thoroughly before deploying

❌ **DON'T:**
- Never manually update invoice payment_status
- Never bypass PaymentService
- Never skip ledger entry creation
- Never ignore orphaned payment alerts
- Never deploy payment changes without testing

---

## Testing Checklist

Before deploying any payment-related changes:

- [ ] Test full payment flow end-to-end
- [ ] Verify invoice_payment record is created
- [ ] Verify 3 ledger entries are created
- [ ] Verify merchant balance updates correctly
- [ ] Verify platform service charge is deducted
- [ ] Test webhook failure scenarios
- [ ] Test database transaction rollback
- [ ] Run orphaned invoice detection
- [ ] Check balance reconciliation

---

## Summary

**Will this happen again?**

Only if:
- ❌ Payments bypass PaymentService
- ❌ Database transactions aren't used properly
- ❌ Webhooks fail without proper error handling
- ❌ Manual database updates are made

**To prevent it:**
- ✅ Always use PaymentService
- ✅ Use database transactions
- ✅ Run daily monitoring (php artisan payments:detect-orphaned-invoices)
- ✅ Never manually mark invoices as paid
- ✅ Fix webhook issues immediately
- ✅ Follow the correct payment flow documented above

---

**Last Updated:** 2026-01-30
**Created By:** Payment Orchestration System Team
