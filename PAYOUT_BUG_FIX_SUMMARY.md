# Payout System Bug Fixes - January 19, 2026

## Critical Issues Found and Fixed

### Issue 1: Duplicate Payout Reversal Bug
**Problem:** The `reversePayoutLedgerEntries()` method had no duplicate prevention. When `retryPayout()` was called multiple times on the same failed payout, it created multiple ADJUSTMENT (credit) entries, artificially inflating the merchant's balance.

**Impact:** Merchant preciouscreativegraphics@gmail.com had one failed payout reversed TWICE, adding ₦526,512.50 to their balance when only ₦263,256.25 should have been added. This allowed them to request two payouts when they only had funds for one.

**Fix Applied:**
- Added duplicate prevention check in `reversePayoutLedgerEntries()` (PayoutService.php:354-366)
- Method now checks if an ADJUSTMENT entry already exists for the payout
- Logs warning and returns early if duplicate reversal is attempted

**Cleanup Performed:**
- Deleted 1 duplicate ADJUSTMENT entry using `FixDuplicatePayoutReversals` command
- Recalculated all ledger balances for affected user
- Marked duplicate payout (PO-STD-696D8C02924F9) as FAILED

### Issue 2: Incorrect Payout Status Workflow
**Problem:** Payouts were marked as COMPLETED immediately after initiating the transfer to Paystack, before the transfer was actually approved or processed. This caused the transfer approval endpoint to decline all transfers (status check on line 69 failed), leaving transfers permanently BLOCKED in Paystack.

**Impact:** All payouts were showing as COMPLETED in our database but remained BLOCKED in Paystack. No merchant actually received their money.

**Fix Applied:**
- Changed `processPayout()` to mark as PROCESSING (not COMPLETED) when transfer is initiated (PayoutService.php:154-175)
- Added webhook handlers for transfer events:
  - `transfer.success` → marks payout as COMPLETED
  - `transfer.failed` → marks payout as FAILED
  - `transfer.reversed` → marks payout as FAILED with reversal reason
- Updated PaystackWebhookController.php with new handlers (lines 48-50, 208-286)

**Correct Workflow Now:**
1. Payout created → PENDING
2. Admin approves → PROCESSING → Transfer initiated in Paystack
3. Paystack calls approval endpoint → Approved (status is PROCESSING)
4. Paystack processes transfer → Sends webhook
5. Webhook received → COMPLETED (or FAILED)

**Production Fix Applied:**
- Changed payout PO-STD-696D86DD05F1F from COMPLETED to PROCESSING
- Payout can now be approved when Paystack retries the approval endpoint
- Or manually approve in Paystack dashboard

## Current Status: Merchant preciouscreativegraphics@gmail.com

**Balance:** ₦0.00 (correct)
**Pending Payouts:** ₦0.00
**Available Balance:** ₦0.00

**Payouts:**
- PO-STD-696D86DD05F1F: PROCESSING (₦263,256.25) - Awaiting Paystack approval/retry
- PO-STD-696D8C02924F9: FAILED (₦263,256.25) - Duplicate payout
- 5 other FAILED payouts (various reasons)

**Ledger Entries:** 10 total, all balanced correctly

## Next Steps

### Immediate Actions:
1. **Approve Blocked Transfer in Paystack:**
   - Log into Paystack dashboard
   - Find transfer TRF_a5gy3rq1nx3umxes (PO-STD-696D86DD05F1F)
   - Manually approve it OR wait for Paystack to retry calling our approval endpoint
   - Once approved, transfer will process and merchant will receive ₦263,256.25

2. **Verify Paystack Webhook Configuration:**
   - Ensure webhook URL is configured: https://kinvoice.ng/api/webhook/paystack
   - Ensure these events are enabled:
     - transfer.success
     - transfer.failed
     - transfer.reversed

3. **Monitor Future Payouts:**
   - Watch for new payout requests
   - Verify they go through: PENDING → PROCESSING → COMPLETED
   - Check Paystack dashboard to confirm transfers are being approved

### Testing:
1. Create a test payout for a small amount (₦100)
2. Verify it follows the correct workflow
3. Check that it gets approved and processed by Paystack
4. Confirm webhook marks it as COMPLETED

## Files Modified

1. `app/Services/Payment/PayoutService.php`
   - Added duplicate prevention in `reversePayoutLedgerEntries()`
   - Changed `processPayout()` to mark as PROCESSING instead of COMPLETED

2. `app/Http/Controllers/PaystackWebhookController.php`
   - Added Payout model import
   - Added transfer event handlers (success, failed, reversed)

3. `app/Console/Commands/FixDuplicatePayoutReversals.php` (NEW)
   - Command to clean up duplicate ADJUSTMENT entries

4. `app/Console/Commands/DiagnoseUserBalance.php` (NEW)
   - Diagnostic command to analyze user balances and identify issues

5. `app/Console/Commands/RecalculateLedgerBalances.php` (NEW)
   - Command to fix balance_after discrepancies in ledger entries

## Prevention Measures Now in Place

1. **Duplicate Reversal Prevention:** Cannot reverse the same payout multiple times
2. **Correct Status Workflow:** Payouts aren't marked as completed until Paystack confirms
3. **Webhook-Driven Completion:** Status updates based on actual Paystack transfer outcomes
4. **Diagnostic Tools:** Commands available to quickly identify and fix ledger issues

## Git Commits

- `83c5e80` - Fix critical duplicate payout reversal bug
- `17486a1` - Fix payout workflow - mark as PROCESSING not COMPLETED on initiation
- `061cc32` - Add diagnostic commands for payout balance troubleshooting

Branch: `feature/payment-orchestration-v2`

## Commands Reference

### Diagnose User Balance Issues
```bash
php artisan payments:diagnose-user-balance {email}
```
Shows all ledger entries, payouts, and identifies any discrepancies.

### Fix Duplicate Reversals
```bash
php artisan payments:fix-duplicate-reversals [--dry-run]
```
Removes duplicate ADJUSTMENT entries for the same payout.

### Recalculate Ledger Balances
```bash
php artisan payments:recalculate-balances [--user=ID] [--dry-run]
```
Fixes balance_after fields in ledger entries.

### Backfill Payment Ledger Entries
```bash
php artisan payments:backfill-ledger [--user=ID] [--dry-run]
```
Creates ledger entries for historical payments that occurred before the ledger system.
