# Browser Testing Guide - Subscription Upgrade/Downgrade

## Prerequisites
- [ ] Development server running: `php artisan serve`
- [ ] User account created and logged in
- [ ] User has active subscription
- [ ] Paystack test keys configured in `.env`

## Access URLs
- **Login:** http://localhost:9000/app/login
- **Plans:** http://localhost:9000/app/subscription-plans
- **My Subscription:** http://localhost:9000/app/my-subscription

## Test Sequence

### Test 1: View Available Plans
**URL:** http://localhost:9000/app/subscription-plans

**What to Check:**
1. ✅ All 4 plans display (Free, Starter, Professional, Business)
2. ✅ Current plan shows "Current Plan" badge
3. ✅ Monthly/Yearly toggle works
4. ✅ Prices update when switching cycles
5. ✅ "Select Plan" buttons visible on other plans
6. ✅ If you have credits, green banner shows balance

**Expected Result:** Clean, professional plan cards with accurate pricing

---

### Test 2: Upgrade to Higher Plan (With Credits)
**Scenario:** User has sufficient credits to cover upgrade

**Setup:**
```sql
-- Add test credits via database or tinker
INSERT INTO account_credits (user_id, type, amount, currency, status, description, expires_at, created_at, updated_at)
VALUES (1, 'prorated_refund', 10000, 'NGN', 'available', 'Test credit', DATE_ADD(NOW(), INTERVAL 1 YEAR), NOW(), NOW());
```

**Steps:**
1. Go to Plans page
2. Note your current plan
3. Click "Select Plan" on higher-tier plan
4. Wait for processing

**Expected Result:**
- ✅ Page reloads/redirects to My Subscription
- ✅ Filament success notification shows credit usage
- ✅ Subscription updated to new plan
- ✅ Credits deducted from balance
- ✅ Email received INSTANTLY (check inbox)
- ✅ No payment page shown

**Check Email:**
- Subject: "Your Subscription Has Been Upgraded"
- Contains old and new plan names
- Shows billing cycle
- Lists new plan features
- Shows next billing date
- No queue delay (received within seconds)

---

### Test 3: Upgrade to Higher Plan (Requires Payment)
**Scenario:** User has no credits or insufficient credits

**Steps:**
1. Ensure user has < upgrade cost in credits
2. Go to Plans page
3. Click "Select Plan" on higher-tier plan
4. Wait for redirect

**Expected Result:**
- ✅ Redirected to Paystack payment page
- ✅ Amount shown is: (newPrice - oldPrice) × (daysRemaining/totalDays)
- ✅ If user has partial credits, amount reduced accordingly
- ✅ Payment page shows correct reference number

**After Payment (Test Mode):**
1. Complete test payment on Paystack
2. Redirected back to app

**Expected on Return:**
- ✅ Redirected to `/app/my-subscription`
- ✅ Success message shown
- ✅ Subscription updated
- ✅ Transaction recorded in database
- ✅ Email received INSTANTLY

**Check Transaction:**
```sql
SELECT * FROM payment_transactions WHERE user_id = 1 ORDER BY created_at DESC LIMIT 1;
```
- Status should be 'successful'
- Type should be 'subscription_upgrade'
- Metadata contains plan_id, credits_applied

---

### Test 4: Downgrade to Lower Plan
**Scenario:** User downgrades and receives credit

**Steps:**
1. Ensure on higher-tier plan
2. Go to Plans page
3. Click "Select Plan" on lower-tier plan
4. Confirm downgrade

**Expected Result:**
- ✅ Immediate downgrade (no payment)
- ✅ Success notification shows credit amount issued
- ✅ Redirected to My Subscription page
- ✅ Credit appears in "Account Credits" section
- ✅ Credit shows expiration date (1 year from now)
- ✅ Email received INSTANTLY

**Check Credit:**
```sql
SELECT * FROM account_credits WHERE user_id = 1 ORDER BY created_at DESC LIMIT 1;
```
- Amount = (oldPrice - newPrice) × (daysRemaining/totalDays)
- Status = 'available'
- Type = 'prorated_refund'
- Expires in 1 year

**Check Email:**
- Subject: "Your Subscription Has Been Downgraded"
- Shows credit amount issued
- Shows credit expiration
- Lists new plan features

---

### Test 5: View Credits on My Subscription Page
**URL:** http://localhost:9000/app/my-subscription

**What to Check:**
1. ✅ "Account Credits" section visible
2. ✅ Large green display shows total available balance
3. ✅ Message: "Your credits will be automatically applied..."
4. ✅ Credit History shows last 5 credits
5. ✅ Each credit shows:
   - Description
   - Created date
   - Expiration date
   - Amount
   - Status badge (available/used)

**Test Credit Display:**
- Amount formatted as ₦X,XXX.XX
- Green for available, gray for used
- Expiration date visible
- Description clear

---

### Test 6: 30-Day Restriction (Annual Plans)
**Scenario:** Annual plan changed, try to change again

**Setup:**
1. Switch to annual billing cycle
2. Change to different plan
3. Immediately try to change again

**Expected Result:**
- ✅ Error notification appears
- ✅ Message: "Annual subscriptions can only be changed once every 30 days"
- ✅ Shows days remaining: "You can change your plan again in X day(s)"

**Verify Database:**
```sql
SELECT last_plan_change_at, billing_cycle FROM subscriptions WHERE user_id = 1;
```
- `last_plan_change_at` should be recent timestamp
- `billing_cycle` should be 'yearly'

**After 30 Days (Manual Test):**
```sql
-- Simulate 30 days passing
UPDATE subscriptions SET last_plan_change_at = DATE_SUB(NOW(), INTERVAL 31 DAY) WHERE user_id = 1;
```
- Try changing plan again
- ✅ Should now succeed

---

### Test 7: Failed Payment Handling
**Scenario:** User starts upgrade but cancels payment

**Steps:**
1. Start upgrade requiring payment
2. Get redirected to Paystack
3. Close browser/cancel payment
4. Manually visit verify URL (if needed)

**Expected Result:**
- ✅ Transaction marked as 'failed' in database
- ✅ Subscription NOT updated
- ✅ User can try again
- ✅ No email sent
- ✅ Error message shown if returning to verify URL

---

### Test 8: Credit FIFO Order
**Scenario:** Verify credits used in correct order

**Setup:**
```sql
-- Create credits with different expiration dates
INSERT INTO account_credits (user_id, type, amount, currency, status, description, expires_at, created_at, updated_at)
VALUES
(1, 'prorated_refund', 3000, 'NGN', 'available', 'Credit 1 - expires soon', DATE_ADD(NOW(), INTERVAL 30 DAY), NOW(), NOW()),
(1, 'prorated_refund', 5000, 'NGN', 'available', 'Credit 2 - expires later', DATE_ADD(NOW(), INTERVAL 1 YEAR), NOW(), NOW());
```

**Test:**
1. Perform upgrade costing ₦4,000
2. Check which credits used

**Expected:**
- ✅ Credit 1 (₦3,000) used first - expires soonest
- ✅ ₦1,000 taken from Credit 2
- ✅ Credit 2 remaining: ₦4,000
- ✅ Credit 1 status: 'used'

**Verify:**
```sql
SELECT id, amount, status, description, expires_at FROM account_credits WHERE user_id = 1 ORDER BY expires_at;
```

---

### Test 9: Partial Credit Coverage
**Scenario:** Credits cover part of upgrade cost

**Setup:**
- User has ₦2,000 in credits
- Upgrade costs ₦5,000

**Steps:**
1. Go to Plans page
2. Note credit balance shown
3. Click upgrade
4. Note Paystack amount

**Expected:**
- ✅ Paystack charges ₦3,000 (₦5,000 - ₦2,000)
- ✅ After payment, all credits used
- ✅ Transaction metadata shows credits_applied = 2000

---

### Test 10: Multiple Plan Changes
**Scenario:** Upgrade → Downgrade → Upgrade

**Steps:**
1. Starter → Professional (upgrade)
2. Professional → Starter (downgrade, get credit)
3. Starter → Professional (upgrade, use credit)

**Expected Flow:**
1. First upgrade: Payment required
2. Downgrade: Credit issued
3. Second upgrade: Credit applied, less/no payment

**Verify:**
- ✅ Credit from downgrade applied to second upgrade
- ✅ Credit balance updates correctly
- ✅ `last_plan_change_at` updated each time
- ✅ Email sent for each change

---

## Testing Checklist Summary

### Core Functionality
- [ ] Upgrade with sufficient credits (instant)
- [ ] Upgrade requiring payment (Paystack flow)
- [ ] Downgrade with credit issuance
- [ ] 30-day restriction enforcement
- [ ] Failed payment handling

### UI/UX
- [ ] Plans page displays correctly
- [ ] Credit banner shows when applicable
- [ ] My Subscription shows credits
- [ ] Credit history displays
- [ ] Success/error notifications work

### Email Notifications
- [ ] Upgrade email instant (not queued)
- [ ] Downgrade email instant
- [ ] Email contains all details
- [ ] Professional formatting

### Data Integrity
- [ ] Credits applied in FIFO order
- [ ] Partial credit coverage works
- [ ] Transaction records accurate
- [ ] Subscription updates correctly
- [ ] Credit expiration respected

### Edge Cases
- [ ] Multiple rapid changes
- [ ] Credit expiration handling
- [ ] Zero-day remaining (end of period)
- [ ] Same plan selection (error)

---

## Debugging Tips

### View Logs
```bash
tail -f storage/logs/laravel.log
```

### Check Database State
```sql
-- Current subscription
SELECT s.*, p.name, p.slug FROM subscriptions s
JOIN plans p ON s.plan_id = p.id
WHERE s.user_id = 1;

-- Available credits
SELECT * FROM account_credits WHERE user_id = 1 AND status = 'available';

-- Recent transactions
SELECT * FROM payment_transactions WHERE user_id = 1 ORDER BY created_at DESC LIMIT 5;
```

### Test Routes Directly
```bash
# Upgrade (requires authentication cookie)
curl -X POST http://localhost:9000/subscription/upgrade \
  -H "Content-Type: application/json" \
  -d '{"plan_slug":"professional"}' \
  -b cookies.txt

# Check route list
php artisan route:list --name=subscription
```

---

## Success Criteria

**Ready for Production When:**
- ✅ All upgrade flows work smoothly
- ✅ All downgrade flows work smoothly
- ✅ Credits display and apply correctly
- ✅ Emails arrive instantly (<5 seconds)
- ✅ Paystack integration verified
- ✅ 30-day restriction enforced
- ✅ No errors in logs
- ✅ Professional user experience

**Current Status:** Backend implementation complete, browser testing pending
