<?php

echo "Completing SubscriptionService update...\n\n";

// Read the current file
$content = file_get_contents('app/Services/SubscriptionService_original.php');

// Get all the enhancement code from a template
$enhancedContent = file_get_contents('app/Services/ProratedCalculationService.php');

// Since the guide exists, let me just create the complete enhanced version from scratch
$finalContent = <<<'PHPFINAL'
<?php

namespace App\Services;

use App\Models\AccountCredit;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\SubscriptionChangedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SubscriptionService
{
    public function __construct(
        private PaystackService $paystackService,
        private ProratedCalculationService $proratedService
    ) {}

    public function subscribe(User $user, Plan $plan, string $cycle = 'monthly'): Subscription
    {
        $amount = $cycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;
        $periodStart = now();
        $periodEnd = $cycle === 'yearly' ? $periodStart->copy()->addYear() : $periodStart->copy()->addMonth();

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

        $subscription->resetUsage();

        Log::info('User subscribed to plan', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'cycle' => $cycle,
        ]);

        return $subscription;
    }

    public function upgrade(Subscription $subscription, Plan $newPlan): array
    {
        $oldPlan = $subscription->plan;

        $canChange = $this->proratedService->canChangePlan($subscription);
        if (!$canChange['allowed']) {
            return [
                'success' => false,
                'message' => $canChange['reason'],
            ];
        }

        $upgradeAmount = $this->proratedService->calculateUpgradePayment(
            $subscription,
            $oldPlan,
            $newPlan
        );

        $creditCalculation = $this->proratedService->applyCreditsToUpgrade(
            $subscription->user_id,
            $upgradeAmount
        );

        if ($creditCalculation['amount_after_credits'] == 0) {
            return $this->completeUpgrade(
                $subscription,
                $newPlan,
                $upgradeAmount,
                $creditCalculation['credits_applied']
            );
        }

        return [
            'success' => true,
            'requires_payment' => true,
            'original_amount' => $upgradeAmount,
            'credits_available' => $creditCalculation['credits_applied'],
            'amount_to_pay' => $creditCalculation['amount_after_credits'],
            'message' => 'Payment required to complete upgrade',
        ];
    }

    public function completeUpgrade(
        Subscription $subscription,
        Plan $newPlan,
        float $totalUpgradeAmount,
        float $creditsApplied = 0,
        int $transactionId = null
    ): array {
        $oldPlan = $subscription->plan;

        DB::transaction(function () use ($subscription, $newPlan, $oldPlan, $creditsApplied, $transactionId) {
            if ($creditsApplied > 0) {
                $this->applyUserCredits($subscription->user_id, $creditsApplied, $transactionId);
            }

            $subscription->update([
                'plan_id' => $newPlan->id,
                'previous_plan_id' => $oldPlan->id,
                'amount' => $subscription->billing_cycle === 'yearly'
                    ? $newPlan->price_yearly
                    : $newPlan->price_monthly,
                'last_plan_change_at' => now(),
            ]);

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

    public function downgrade(Subscription $subscription, Plan $newPlan): array
    {
        $oldPlan = $subscription->plan;

        $canChange = $this->proratedService->canChangePlan($subscription);
        if (!$canChange['allowed']) {
            return [
                'success' => false,
                'message' => $canChange['reason'],
            ];
        }

        $creditAmount = $this->proratedService->calculateCredit(
            $subscription,
            $oldPlan,
            $newPlan
        );

        DB::transaction(function () use ($subscription, $newPlan, $oldPlan, $creditAmount) {
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
                    'expires_at' => now()->addYear(),
                ]);
            }

            $subscription->update([
                'plan_id' => $newPlan->id,
                'previous_plan_id' => $oldPlan->id,
                'amount' => $subscription->billing_cycle === 'yearly'
                    ? $newPlan->price_yearly
                    : $newPlan->price_monthly,
                'last_plan_change_at' => now(),
            ]);

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

    private function applyUserCredits(int $userId, float $amount, int $transactionId = null): void
    {
        $credits = AccountCredit::where('user_id', $userId)
            ->available()
            ->orderBy('expires_at', 'asc')
            ->get();

        $remainingAmount = $amount;

        foreach ($credits as $credit) {
            if ($remainingAmount <= 0) {
                break;
            }

            if ($credit->amount <= $remainingAmount) {
                $credit->markAsUsed($transactionId);
                $remainingAmount -= $credit->amount;
            } else {
                $remainingCredit = $credit->amount - $remainingAmount;
                $credit->markAsUsed($transactionId);

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

    public function cancel(Subscription $subscription, bool $immediately = false): Subscription
    {
        if ($immediately) {
            $subscription->update([
                'status' => 'canceled',
                'canceled_at' => now(),
                'expires_at' => now(),
            ]);
        } else {
            $subscription->update([
                'status' => 'canceled',
                'canceled_at' => now(),
                'expires_at' => $subscription->current_period_end,
            ]);
        }

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

    public function reactivate(Subscription $subscription): Subscription
    {
        $subscription->update([
            'status' => 'active',
            'canceled_at' => null,
            'expires_at' => null,
        ]);

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

        $subscription->resetUsage();

        Log::info('Subscription renewed', [
            'subscription_id' => $subscription->id,
        ]);

        return $subscription->fresh();
    }

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
        }

        Log::info('Checked for expired subscriptions', [
            'expired_count' => $expiredSubscriptions->count(),
        ]);
    }

    public function resetMonthlyUsage(): void
    {
        $activeSubscriptions = Subscription::where('status', 'active')->get();

        foreach ($activeSubscriptions as $subscription) {
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

    private function shouldResetUsage(Subscription $subscription): bool
    {
        if (!$subscription->usage_reset_at) {
            return true;
        }

        if ($subscription->billing_cycle === 'monthly') {
            return $subscription->usage_reset_at->addMonth()->isPast();
        }

        if ($subscription->billing_cycle === 'yearly') {
            return $subscription->usage_reset_at->addYear()->isPast();
        }

        return false;
    }

    public function assignFreePlan(User $user): ?Subscription
    {
        $freePlan = Plan::where('slug', 'free')->first();

        if (!$freePlan) {
            Log::warning('Free plan not found when assigning to user', ['user_id' => $user->id]);
            return null;
        }

        return $this->subscribe($user, $freePlan, 'monthly');
    }

    public function canChangePlan(Subscription $subscription, Plan $newPlan): array
    {
        $currentPlan = $subscription->plan;

        if ($currentPlan->id === $newPlan->id) {
            return [
                'can_change' => false,
                'reason' => 'Already subscribed to this plan',
            ];
        }

        $timeRestriction = $this->proratedService->canChangePlan($subscription);
        if (!$timeRestriction['allowed']) {
            return [
                'can_change' => false,
                'reason' => $timeRestriction['reason'],
            ];
        }

        if ($currentPlan->isFree()) {
            return [
                'can_change' => true,
                'type' => 'upgrade',
            ];
        }

        $isUpgrade = $newPlan->price_monthly > $currentPlan->price_monthly;

        return [
            'can_change' => true,
            'type' => $isUpgrade ? 'upgrade' : 'downgrade',
        ];
    }
}
PHPFINAL;

file_put_contents('app/Services/SubscriptionService.php', $finalContent);

echo "✓ Complete SubscriptionService written\n\n";
echo "Testing syntax...\n";

$output = [];
$return = 0;
exec('php -l app/Services/SubscriptionService.php 2>&1', $output, $return);

if ($return === 0) {
    echo "✓ Syntax check passed!\n";
    echo "✓ SubscriptionService fully updated with all enhancements!\n";
} else {
    echo "✗ Syntax error:\n";
    echo implode("\n", $output) . "\n";
}
