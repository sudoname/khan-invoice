<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use Carbon\Carbon;

class ProratedCalculationService
{
    /**
     * Calculate prorated credit for a downgrade
     */
    public function calculateCredit(Subscription $subscription, Plan $oldPlan, Plan $newPlan): float
    {
        $daysRemaining = $this->getDaysRemaining($subscription);
        $totalDays = $this->getTotalDays($subscription);

        // Calculate the old plan's daily rate
        $oldPrice = $subscription->billing_cycle === 'yearly'
            ? $oldPlan->price_yearly
            : $oldPlan->price_monthly;

        // Calculate the new plan's daily rate
        $newPrice = $subscription->billing_cycle === 'yearly'
            ? $newPlan->price_yearly
            : $newPlan->price_monthly;

        // Credit = (old daily rate - new daily rate) * days remaining
        $oldDailyRate = $oldPrice / $totalDays;
        $newDailyRate = $newPrice / $totalDays;

        $credit = ($oldDailyRate - $newDailyRate) * $daysRemaining;

        return max(0, round($credit, 2));
    }

    /**
     * Calculate prorated charge for an upgrade
     */
    public function calculateUpgradePayment(Subscription $subscription, Plan $oldPlan, Plan $newPlan): float
    {
        $daysRemaining = $this->getDaysRemaining($subscription);
        $totalDays = $this->getTotalDays($subscription);

        // Calculate the old plan's daily rate
        $oldPrice = $subscription->billing_cycle === 'yearly'
            ? $oldPlan->price_yearly
            : $oldPlan->price_monthly;

        // Calculate the new plan's daily rate
        $newPrice = $subscription->billing_cycle === 'yearly'
            ? $newPlan->price_yearly
            : $newPlan->price_monthly;

        // Charge = (new daily rate - old daily rate) * days remaining
        $oldDailyRate = $oldPrice / $totalDays;
        $newDailyRate = $newPrice / $totalDays;

        $charge = ($newDailyRate - $oldDailyRate) * $daysRemaining;

        return max(0, round($charge, 2));
    }

    /**
     * Check if a subscription can be changed
     * Annual subscriptions can only be changed once within 30 days
     */
    public function canChangePlan(Subscription $subscription): array
    {
        // Free plan users can always change
        if ($subscription->plan->isFree()) {
            return [
                'allowed' => true,
                'reason' => null,
            ];
        }

        // For annual subscriptions, check 30-day restriction
        if ($subscription->billing_cycle === 'yearly' && $subscription->last_plan_change_at) {
            $daysSinceLastChange = Carbon::parse($subscription->last_plan_change_at)->diffInDays(now());

            if ($daysSinceLastChange < 30) {
                $daysRemaining = 30 - $daysSinceLastChange;
                return [
                    'allowed' => false,
                    'reason' => "Annual subscriptions can only be changed once every 30 days. You can change your plan again in {$daysRemaining} day(s).",
                ];
            }
        }

        // For monthly subscriptions, no restriction
        return [
            'allowed' => true,
            'reason' => null,
        ];
    }

    /**
     * Get the number of days remaining in the current billing cycle
     */
    public function getDaysRemaining(Subscription $subscription): int
    {
        return now()->diffInDays($subscription->next_billing_date, false);
    }

    /**
     * Get the total number of days in the billing cycle
     */
    public function getTotalDays(Subscription $subscription): int
    {
        if ($subscription->billing_cycle === 'yearly') {
            return 365;
        }

        // For monthly, calculate actual days in the month
        return now()->daysInMonth;
    }

    /**
     * Get available credits for a user
     */
    public function getAvailableCredits(int $userId): float
    {
        return \App\Models\AccountCredit::where('user_id', $userId)
            ->available()
            ->sum('amount');
    }

    /**
     * Apply credits to an upgrade amount
     * Returns the amount after credits are applied
     */
    public function applyCreditsToUpgrade(int $userId, float $upgradeAmount): array
    {
        $availableCredits = $this->getAvailableCredits($userId);

        if ($availableCredits <= 0) {
            return [
                'amount_after_credits' => $upgradeAmount,
                'credits_applied' => 0,
                'remaining_credits' => 0,
            ];
        }

        // If credits cover the entire upgrade amount
        if ($availableCredits >= $upgradeAmount) {
            return [
                'amount_after_credits' => 0,
                'credits_applied' => $upgradeAmount,
                'remaining_credits' => $availableCredits - $upgradeAmount,
            ];
        }

        // If credits partially cover the upgrade
        return [
            'amount_after_credits' => $upgradeAmount - $availableCredits,
            'credits_applied' => $availableCredits,
            'remaining_credits' => 0,
        ];
    }
}
