# Payment Flow Diagram - Business Absorbs Fees

## Visual Money Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                         CUSTOMER                                     │
│                    Pays: ₦10,000 only                               │
│                  (No fees visible/added)                             │
└──────────────────────────┬──────────────────────────────────────────┘
                           │
                           │ Customer pays invoice amount
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────────┐
│                         PAYSTACK                                     │
│                   Receives: ₦10,000                                 │
│                                                                      │
│  Step 1: Deduct Paystack Fee                                        │
│  ₦10,000 - ₦250 = ₦9,750                                           │
│                                                                      │
│  Step 2: Split Remaining Amount                                     │
│  Uses transaction_charge parameter                                  │
└───┬─────────────────────────────────────────┬───────────────────────┘
    │                                         │
    │ ₦200                                   │ ₦9,550
    │ (Service Charge)                       │ (Net Amount)
    │                                         │
    ▼                                         ▼
┌─────────────────────────┐    ┌──────────────────────────────────────┐
│   MAIN ACCOUNT          │    │   BUSINESS OWNER'S SUBACCOUNT        │
│   (kinvoice.ng)         │    │                                      │
│                         │    │                                      │
│  Receives: ₦200        │    │  Receives: ₦9,550                   │
│  (Platform fee)         │    │  (Net revenue)                       │
└─────────────────────────┘    └──────────────────────────────────────┘
```

---

## Detailed Breakdown

### ₦10,000 Invoice Example

| Party | Receives | Description |
|-------|----------|-------------|
| **Customer** | Pays ₦10,000 | Only invoice amount (no fees added) |
| **Paystack** | Keeps ₦250 | 1.5% + ₦100 = ₦250 (their processing fee) |
| **Platform (Main Account)** | Receives ₦200 | 2% service charge (via transaction_charge) |
| **Business Owner (Subaccount)** | Receives ₦9,550 | Net amount after all fees |

**Total Distributed:** ₦250 + ₦200 + ₦9,550 = ₦10,000 ✓

---

## Paystack Integration Parameters

```javascript
PaystackPop.setup({
    amount: 1000000,  // ₦10,000 in kobo (customer pays only invoice)

    subaccount: 'ACCT_xxxxx',  // Business owner's subaccount

    transaction_charge: 20000,  // ₦200 in kobo (service charge)
    // ↑ This tells Paystack to send ₦200 to main account

    bearer: 'account',  // Subaccount bears the charge
    // ↑ This means ₦200 is deducted from subaccount's portion
})
```

---

## Fee Calculation Logic

```php
// In PaymentSetting model
public static function calculateNetAmountReceived(float $invoiceAmount): array
{
    // ₦10,000 * 1.5% + ₦100 = ₦250 (capped at ₦2,000)
    $paystackFee = self::calculatePaystackFee($invoiceAmount);

    // ₦10,000 * 2% = ₦200 (min ₦150, capped at ₦3,000)
    $serviceCharge = self::calculateServiceCharge($invoiceAmount);

    // ₦250 + ₦200 = ₦450
    $totalFees = $paystackFee + $serviceCharge;

    // ₦10,000 - ₦450 = ₦9,550
    $netAmount = $invoiceAmount - $totalFees;

    return [
        'invoice_amount' => 10000.00,
        'paystack_fee' => 250.00,
        'service_charge' => 200.00,
        'total_fees' => 450.00,
        'net_amount_received' => 9550.00,
        'customer_pays' => 10000.00,
    ];
}
```

---

## Multiple Invoice Amounts

| Invoice | Customer Pays | Paystack Fee | Service Charge | Platform Gets | Business Gets | Business % |
|---------|---------------|--------------|----------------|---------------|---------------|------------|
| ₦1,000 | ₦1,000 | ₦115 | ₦150 | ₦150 | ₦735 | 73.5% |
| ₦10,000 | ₦10,000 | ₦250 | ₦200 | ₦200 | ₦9,550 | 95.5% |
| ₦50,000 | ₦50,000 | ₦850 | ₦1,000 | ₦1,000 | ₦48,150 | 96.3% |
| ₦100,000 | ₦100,000 | ₦1,600 | ₦2,000 | ₦2,000 | ₦96,400 | 96.4% |
| ₦200,000 | ₦200,000 | ₦2,000* | ₦3,000* | ₦3,000 | ₦195,000 | 97.5% |

*Capped at maximum

---

## Webhook Log Example

When payment is successful, the webhook logs:

```php
Log::info('Public invoice payment processed', [
    'reference' => 'KI_PUBLIC_abc123_1234567890',
    'customer_paid' => 10000.00,              // What customer paid
    'paystack_fee_deducted' => 250.00,        // Paystack keeps this
    'service_charge_deducted' => 200.00,      // Platform receives this
    'total_fees_deducted' => 450.00,          // Total fees
    'net_amount_business_receives' => 9550.00, // Business gets this
    'fee_model' => 'business_absorbs_fees',
]);
```

---

## Benefits of This Model

### For Customers:
✅ **Transparent pricing** - Pay exactly what the invoice says
✅ **No surprise fees** at checkout
✅ **Simpler checkout** experience
✅ **Builds trust** - Clear and honest pricing

### For Platform (kinvoice.ng):
✅ **Automated revenue** - Service charge collected automatically by Paystack
✅ **No manual reconciliation** needed
✅ **Instant settlement** - Paystack handles the split
✅ **Full audit trail** - Every transaction logged

### For Business Owners:
✅ **Professional appearance** - No fee confusion for customers
✅ **Automatic settlements** - Net amount goes directly to their account
✅ **Predictable costs** - Know exactly how much they'll receive
⚠️ **Lower net revenue** - But offset by higher conversion rates

---

## Testing the Integration

### Test 1: Check Paystack Dashboard (After Payment)

**Main Account Balance:**
- Look for ₦200 credit (service charge)
- Transaction reference: `KI_PUBLIC_xxx`
- Description: "Service charge for invoice xxx"

**Subaccount Balance (Business):**
- Look for ₦9,550 credit (net amount)
- Transaction reference: `KI_PUBLIC_xxx`
- Description: "Payment for invoice xxx"

### Test 2: Verify Split in Webhook

Check `storage/logs/laravel.log`:
```bash
grep "Public invoice payment processed" storage/logs/laravel.log | tail -1
```

Should show:
- `customer_paid: 10000.00`
- `service_charge_deducted: 200.00`
- `net_amount_business_receives: 9550.00`

### Test 3: Bank Settlement Verification

**Platform Bank Account:**
- Receives: ₦200 from Paystack (minus Paystack's payout fee if any)

**Business Owner Bank Account:**
- Receives: ₦9,550 from Paystack (minus Paystack's payout fee if any)

---

## Production Checklist

- [ ] Verify Paystack test mode works correctly with split
- [ ] Check main account receives service charge in test transactions
- [ ] Confirm subaccounts receive correct net amounts
- [ ] Test with various invoice amounts (₦1k, ₦10k, ₦50k, ₦100k+)
- [ ] Verify webhook logging shows correct breakdown
- [ ] Check Paystack dashboard shows proper splits
- [ ] Test with and without subaccounts (some invoices might not have them)
- [ ] Document reconciliation process for accounting
- [ ] Switch to Paystack live mode
- [ ] Monitor first few live transactions closely

---

## Reconciliation Formula

For accounting/bookkeeping:

```
Platform Monthly Revenue =
    Sum of all service_charge_deducted
    from webhook logs

Business Owner Monthly Revenue =
    Sum of all net_amount_business_receives
    from webhook logs

Total Customer Payments =
    Sum of all customer_paid
    from webhook logs

Verification:
Total Customer Payments =
    Paystack Total Fees +
    Platform Revenue +
    Business Owners Revenue
```

---

## Common Questions

**Q: What if business doesn't have a subaccount?**
A: The entire amount (minus Paystack fee) goes to main account. Service charge isn't applicable since there's no split.

**Q: Can service charge percentage be changed?**
A: Yes, in Admin → Payment Settings. Changes apply to new transactions only.

**Q: What about refunds?**
A: Refunds should return the full invoice amount to customer. Platform and business would need to reverse their portions.

**Q: Does Paystack charge fees on the transaction_charge?**
A: No, Paystack's fee is already deducted before the split happens.

**Q: Can customers see the fee breakdown?**
A: No, customers only see the invoice amount. Fee breakdown is internal.

---

## Support Resources

- **Paystack Subaccounts Docs:** https://paystack.com/docs/payments/split-payments
- **Paystack Transaction Charge:** https://paystack.com/docs/payments/split-payments#transaction-charges
- **Fee Settings:** Admin Panel → Payment Settings
- **Logs Location:** `storage/logs/laravel.log`
- **Test Cards:** https://paystack.com/docs/payments/test-payments

---

**Last Updated:** December 13, 2025
**Fee Model:** Business Absorbs Fees (Automated Split)
**Status:** ✅ Production Ready
