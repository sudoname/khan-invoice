# SubscriptionService Enhancement Guide

## Overview
This document provides step-by-step instructions to enhance the SubscriptionService with subscription change management (upgrades/downgrades with prorated calculations, credits, and notifications).

## File Location
`app/Services/SubscriptionService.php`

---

## Step 1: Update Use Statements

**Location:** Lines 5-9

**Current:**
```php
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
```

**Replace with:**
```php
use App\Models\AccountCredit;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\SubscriptionChangedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
```

---

## Step 2: Update Constructor

**Location:** Lines 13-15

**Current:**
```php
public function __construct(
    private PaystackService $paystackService
) {}
```

**Replace with:**
```php
public function __construct(
    private PaystackService $paystackService,
    private ProratedCalculationService $proratedService
) {}
```

---

## Step 3: Replace upgrade() Method

**Location:** Lines 58-80

**Current:**
```php
public function upgrade(Subscription $subscription, Plan $newPlan): Subscription
{
    $oldPlan = $subscription->plan;

    // Update subscription
    $subscription->update([
        'plan_id' => $newPlan->id,
        'amount' => $subscription->billing_cycle === 'yearly'
            ? $newPlan->price_yearly
            : $newPlan->price_monthly,
    ]);

    Log::info('Subscription upgraded', [
        'subscription_id' => $subscription->id,
        'old_plan' => $oldPlan->name,
        'new_plan' => $newPlan->name,
    ]);

    return $subscription->fresh();
}
```

**Replace with:**
```php
public function upgrade(Subscription $subscription, Plan $newPlan): array
{
    $oldPlan = $subscription->plan;

    // Check if plan change is allowed
    $canChange = $this->proratedService->canChangePlan($subscription);
    if (!$canChange['allowed']) {
        return [
            'success' => false,
            'message' => $canChange['reason'],
        ];
    }

    // Calculate prorated upgrade amount
    $upgradeAmount = $this->proratedService->calculateUpgradePayment(
        $subscription,
        $oldPlan,
        $newPlan
    );

    // Check if user has available credits
    $creditCalculation = $this->proratedService->applyCreditsToUpgrade(
        $subscription->user_id,
        $upgradeAmount
    );

    // If credits cover the entire upgrade, apply them and complete upgrade
    if ($creditCalculation['amount_after_credits'] == 0) {
        return $this->completeUpgrade(
            $subscription,
            $newPlan,
            $upgradeAmount,
            $creditCalculation['credits_applied']
        );
    }

    // Otherwise, return payment details for Paystack
    return [
        'success' => true,
        'requires_payment' => true,
        'original_amount' => $upgradeAmount,
        'credits_available' => $creditCalculation['credits_applied'],
        'amount_to_pay' => $creditCalculation['amount_after_credits'],
        'message' => 'Payment required to complete upgrade',
    ];
}
```

---

## Step 4: Add completeUpgrade() Method

**Location:** After upgrade() method (around line 80)

**Add this new method:**
```php
/**
 * Complete an upgrade after payment (or if covered by credits)
 */
public function completeUpgrade(
    Subscription $subscription,
    Plan $newPlan,
    float $totalUpgradeAmount,
    float $creditsApplied = 0,
    int $transactionId = null
): array {
    $oldPlan = $subscription->plan;

    DB::transaction(function () use ($subscription, $newPlan, $oldPlan, $creditsApplied, $transactionId) {
        // Apply credits if any
        if ($creditsApplied > 0) {
            $this->applyUserCredits($subscription->user_id, $creditsApplied, $transactionId);
        }

        // Update subscription
        $subscription->update([
            'plan_id' => $newPlan->id,
            'previous_plan_id' => $oldPlan->id,
            'amount' => $subscription->billing_cycle === 'yearly'
                ? $newPlan->price_yearly
                : $newPlan->price_monthly,
            'last_plan_change_at' => now(),
        ]);

        // Send notification
        $subscription->user->notify(new SubscriptionChangedNotification(
            subscription: $subscription->fresh(),
            oldPlan: $oldPlan,
            newPlan: $newPlan,
            changeType: 'upgrade',
            creditIssued: null,
            amountCharged: $totalUpgradeAmount
        ));
    });

    Log::info('Subscription upgraded', [
        'subscription_id' => $subscription->id,
        'old_plan' => $oldPlan->name,
        'new_plan' => $newPlan->name,
        'amount_charged' => $totalUpgradeAmount,
        'credits_applied' => $creditsApplied,
    ]);

    return [
        'success' => true,
        'message' => 'Subscription upgraded successfully',
        'subscription' => $subscription->fresh(),
    ];
}
```

---

## Step 5: Replace downgrade() Method

**Location:** Lines 82-104

**Current:**
```php
public function downgrade(Subscription $subscription, Plan $newPlan): Subscription
{
    $oldPlan = $subscription->plan;

    // Downgrade happens at end of current period
    $subscription->update([
        'plan_id' => $newPlan->id,
        'amount' => $subscription->billing_cycle === 'yearly'
            ? $newPlan->price_yearly
            : $newPlan->price_monthly,
    ]);

    Log::info('Subscription downgraded', [
        'subscription_id' => $subscription->id,
        'old_plan' => $oldPlan->name,
        'new_plan' => $newPlan->name,
    ]);

    return $subscription->fresh();
}
```

**Replace with:**
```php
public function downgrade(Subscription $subscription, Plan $newPlan): array
{
    $oldPlan = $subscription->plan;

    // Check if plan change is allowed
    $canChange = $this->proratedService->canChangePlan($subscription);
    if (!$canChange['allowed']) {
        return [
            'success' => false,
            'message' => $canChange['reason'],
        ];
    }

    // Calculate prorated credit
    $creditAmount = $this->proratedService->calculateCredit(
        $subscription,
        $oldPlan,
        $newPlan
    );

    DB::transaction(function () use ($subscription, $newPlan, $oldPlan, $creditAmount) {
        // Issue credit if applicable
        if ($creditAmount > 0) {
            AccountCredit::create([
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->id,
                'type' => 'prorated_refund',
                'amount' => $creditAmount,
                'currency' => $subscription->currency,
                'status' => 'available',
                'description' => "Prorated credit from downgrade: {$oldPlan->name} to {$newPlan->name}",
                'metadata' => [
                    'old_plan_id' => $oldPlan->id,
                    'new_plan_id' => $newPlan->id,
                    'downgrade_date' => now()->toDateString(),
                ],
                'expires_at' => now()->addYear(), // Credits expire after 1 year
            ]);
        }

        // Update subscription
        $subscription->update([
            'plan_id' => $newPlan->id,
            'previous_plan_id' => $oldPlan->id,
            'amount' => $subscription->billing_cycle === 'yearly'
                ? $newPlan->price_yearly
                : $newPlan->price_monthly,
            'last_plan_change_at' => now(),
        ]);

        // Send notification
        $subscription->user->notify(new SubscriptionChangedNotification(
            subscription: $subscription->fresh(),
            oldPlan: $oldPlan,
            newPlan: $newPlan,
            changeType: 'downgrade',
            creditIssued: $creditAmount,
            amountCharged: null
        ));
    });

    Log::info('Subscription downgraded', [
        'subscription_id' => $subscription->id,
        'old_plan' => $oldPlan->name,
        'new_plan' => $newPlan->name,
        'credit_issued' => $creditAmount,
    ]);

    return [
        'success' => true,
        'message' => 'Subscription downgraded successfully',
        'credit_issued' => $creditAmount,
        'subscription' => $subscription->fresh(),
    ];
}
```

---

## Step 6: Add applyUserCredits() Method

**Location:** After downgrade() method

**Add this new private method:**
```php
/**
 * Apply available credits for a user
 */
private function applyUserCredits(int $userId, float $amount, int $transactionId = null): void
{
    $credits = AccountCredit::where('user_id', $userId)
        ->available()
        ->orderBy('expires_at', 'asc') // Use credits closest to expiration first
        ->get();

    $remainingAmount = $amount;

    foreach ($credits as $credit) {
        if ($remainingAmount <= 0) {
            break;
        }

        if ($credit->amount <= $remainingAmount) {
            // Use entire credit
            $credit->markAsUsed($transactionId);
            $remainingAmount -= $credit->amount;
        } else {
            // Partial use - split the credit
            $usedAmount = $remainingAmount;
            $remainingCredit = $credit->amount - $usedAmount;

            // Mark original as used
            $credit->markAsUsed($transactionId);

            // Create new credit for remaining amount
            AccountCredit::create([
                'user_id' => $credit->user_id,
                'subscription_id' => $credit->subscription_id,
                'type' => $credit->type,
                'amount' => $remainingCredit,
                'currency' => $credit->currency,
                'status' => 'available',
                'description' => $credit->description . ' (partial use)',
                'metadata' => $credit->metadata,
                'expires_at' => $credit->expires_at,
            ]);

            $remainingAmount = 0;
        }
    }
}
```

---

## Step 7: Update canChangePlan() Method

**Location:** Lines 304-334

**Current:**
```php
public function canChangePlan(Subscription $subscription, Plan $newPlan): array
{
    $currentPlan = $subscription->plan;

    // Can't "change" to same plan
    if ($currentPlan->id === $newPlan->id) {
        return [
            'can_change' => false,
            'reason' => 'Already subscribed to this plan',
        ];
    }

    // Can't downgrade from free (doesn't make sense)
    if ($currentPlan->isFree()) {
        return [
            'can_change' => true,
            'type' => 'upgrade',
        ];
    }

    // Determine if upgrade or downgrade
    $isUpgrade = $newPlan->price_monthly > $currentPlan->price_monthly;

    return [
        'can_change' => true,
        'type' => $isUpgrade ? 'upgrade' : 'downgrade',
    ];
}
```

**Replace with:**
```php
public function canChangePlan(Subscription $subscription, Plan $newPlan): array
{
    $currentPlan = $subscription->plan;

    // Can't "change" to same plan
    if ($currentPlan->id === $newPlan->id) {
        return [
            'can_change' => false,
            'reason' => 'Already subscribed to this plan',
        ];
    }

    // Check 30-day restriction for annual plans
    $timeRestriction = $this->proratedService->canChangePlan($subscription);
    if (!$timeRestriction['allowed']) {
        return [
            'can_change' => false,
            'reason' => $timeRestriction['reason'],
        ];
    }

    // Can't downgrade from free (doesn't make sense)
    if ($currentPlan->isFree()) {
        return [
            'can_change' => true,
            'type' => 'upgrade',
        ];
    }

    // Determine if upgrade or downgrade
    $isUpgrade = $newPlan->price_monthly > $currentPlan->price_monthly;

    return [
        'can_change' => true,
        'type' => $isUpgrade ? 'upgrade' : 'downgrade',
    ];
}
```

---

## Summary of Changes

### Return Type Changes
- `upgrade()`: Changed from `Subscription` to `array`
- `downgrade()`: Changed from `Subscription` to `array`

### New Methods Added
1. `completeUpgrade()` - Finalizes upgrade after payment
2. `applyUserCredits()` - Applies user credits to charges

### Updated Methods
1. `upgrade()` - Now handles prorated calculations, credits, and payment requirements
2. `downgrade()` - Now issues credits and sends notifications
3. `canChangePlan()` - Now checks 30-day restriction for annual plans

### Dependencies Added
- `ProratedCalculationService` (constructor injection)
- `AccountCredit` model
- `SubscriptionChangedNotification`
- `DB` facade for transactions

---

## Testing After Implementation

Run these commands to verify the changes:

```bash
# Check syntax
php -l app/Services/SubscriptionService.php

# Run migrations
php artisan migrate

# Test in Tinker
php artisan tinker
```

In Tinker, test:
```php
$user = User::first();
$subscription = $user->subscription;
$newPlan = Plan::where('slug', 'professional')->first();

// Test upgrade
app(App\Services\SubscriptionService::class)->upgrade($subscription, $newPlan);

// Test canChangePlan
app(App\Services\SubscriptionService::class)->canChangePlan($subscription, $newPlan);
```

---

## Next Steps

After implementing these changes:
1. Create `SubscriptionUpgradeController` for payment verification
2. Update Filament pages to show upgrade/downgrade buttons
3. Add credit balance display to subscription page
4. Test complete flow with Paystack payments
