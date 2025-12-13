<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SubscriptionService
{
    public function __construct(
        private PaystackService $paystackService
    ) {}

    /**
     * Subscribe a user to a plan
     */
    public function subscribe(User $user, Plan $plan, string $cycle = 'monthly'): Subscription
    {
        // Calculate amount based on cycle
        $amount = $cycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;

        // Calculate period dates
        $periodStart = now();
        $periodEnd = $cycle === 'yearly' ? $periodStart->copy()->addYear() : $periodStart->copy()->addMonth();

        // Create or update subscription
        $subscription = Subscription::updateOrCreate(
            ['user_id' => $user->id],
            [
                'plan_id' => $plan->id,
                'status' => 'active',
                'billing_cycle' => $cycle,
                'amount' => $amount,
                'currency' => $plan->currency,
                'current_period_start' => $periodStart,
                'current_period_end' => $periodEnd,
                'trial_ends_at' => null,
                'canceled_at' => null,
                'expires_at' => null,
            ]
        );

        // Reset usage counters
        $subscription->resetUsage();

        Log::info('User subscribed to plan', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'cycle' => $cycle,
        ]);

        return $subscription;
    }

    /**
     * Upgrade a subscription to a higher plan
     */
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

    /**
     * Downgrade a subscription to a lower plan
     */
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

    /**
     * Cancel a subscription
     */
    public function cancel(Subscription $subscription, bool $immediately = false): Subscription
    {
        if ($immediately) {
            $subscription->update([
                'status' => 'canceled',
                'canceled_at' => now(),
                'expires_at' => now(),
            ]);
        } else {
            // Grace period - allow until end of current period
            $subscription->update([
                'status' => 'canceled',
                'canceled_at' => now(),
                'expires_at' => $subscription->current_period_end,
            ]);
        }

        // Cancel on Paystack if subscription code exists
        if ($subscription->paystack_subscription_code && $subscription->paystack_email_token) {
            $this->paystackService->cancelSubscription(
                $subscription->paystack_subscription_code,
                $subscription->paystack_email_token
            );
        }

        Log::info('Subscription canceled', [
            'subscription_id' => $subscription->id,
            'immediately' => $immediately,
        ]);

        return $subscription->fresh();
    }

    /**
     * Reactivate a canceled subscription
     */
    public function reactivate(Subscription $subscription): Subscription
    {
        $subscription->update([
            'status' => 'active',
            'canceled_at' => null,
            'expires_at' => null,
        ]);

        // Re-enable on Paystack if subscription code exists
        if ($subscription->paystack_subscription_code && $subscription->paystack_email_token) {
            $this->paystackService->enableSubscription(
                $subscription->paystack_subscription_code,
                $subscription->paystack_email_token
            );
        }

        Log::info('Subscription reactivated', [
            'subscription_id' => $subscription->id,
        ]);

        return $subscription->fresh();
    }

    /**
     * Switch billing cycle (monthly <-> yearly)
     */
    public function switchBillingCycle(Subscription $subscription, string $newCycle): Subscription
    {
        $plan = $subscription->plan;
        $newAmount = $newCycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;

        $subscription->update([
            'billing_cycle' => $newCycle,
            'amount' => $newAmount,
        ]);

        Log::info('Billing cycle switched', [
            'subscription_id' => $subscription->id,
            'new_cycle' => $newCycle,
        ]);

        return $subscription->fresh();
    }

    /**
     * Renew a subscription for the next period
     */
    public function renew(Subscription $subscription): Subscription
    {
        $periodStart = $subscription->current_period_end ?? now();
        $periodEnd = $subscription->billing_cycle === 'yearly'
            ? $periodStart->copy()->addYear()
            : $periodStart->copy()->addMonth();

        $subscription->update([
            'status' => 'active',
            'current_period_start' => $periodStart,
            'current_period_end' => $periodEnd,
            'canceled_at' => null,
            'expires_at' => null,
        ]);

        // Reset usage counters
        $subscription->resetUsage();

        Log::info('Subscription renewed', [
            'subscription_id' => $subscription->id,
        ]);

        return $subscription->fresh();
    }

    /**
     * Check and expire subscriptions (run daily via scheduler)
     */
    public function checkAndExpireSubscriptions(): void
    {
        $expiredSubscriptions = Subscription::where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($expiredSubscriptions as $subscription) {
            $subscription->update(['status' => 'expired']);

            Log::info('Subscription expired', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
            ]);

            // Optionally send expiration notification
        }

        Log::info('Checked for expired subscriptions', [
            'expired_count' => $expiredSubscriptions->count(),
        ]);
    }

    /**
     * Reset monthly usage counters (run on 1st of each month)
     */
    public function resetMonthlyUsage(): void
    {
        $activeSubscriptions = Subscription::where('status', 'active')->get();

        foreach ($activeSubscriptions as $subscription) {
            // Check if it's time to reset (monthly or yearly on anniversary)
            if ($this->shouldResetUsage($subscription)) {
                $subscription->resetUsage();

                Log::info('Usage reset for subscription', [
                    'subscription_id' => $subscription->id,
                ]);
            }
        }

        Log::info('Monthly usage reset complete', [
            'subscriptions_processed' => $activeSubscriptions->count(),
        ]);
    }

    /**
     * Check if subscription usage should be reset
     */
    private function shouldResetUsage(Subscription $subscription): bool
    {
        // If never reset, reset now
        if (!$subscription->usage_reset_at) {
            return true;
        }

        // For monthly subscriptions, reset if last reset was over a month ago
        if ($subscription->billing_cycle === 'monthly') {
            return $subscription->usage_reset_at->addMonth()->isPast();
        }

        // For yearly subscriptions, reset if last reset was over a year ago
        if ($subscription->billing_cycle === 'yearly') {
            return $subscription->usage_reset_at->addYear()->isPast();
        }

        return false;
    }

    /**
     * Assign free plan to a new user
     */
    public function assignFreePlan(User $user): ?Subscription
    {
        $freePlan = Plan::where('slug', 'free')->first();

        if (!$freePlan) {
            Log::warning('Free plan not found when assigning to user', ['user_id' => $user->id]);
            return null;
        }

        return $this->subscribe($user, $freePlan, 'monthly');
    }

    /**
     * Check if a user can upgrade/downgrade to a specific plan
     */
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
}
