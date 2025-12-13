<?php

echo "Updating SubscriptionService...\n";

$original = file_get_contents('app/Services/SubscriptionService_original.php');

// Step 1 & 2: Add use statements and update constructor
$original = str_replace(
    "use App\\Models\\Plan;",
    "use App\\Models\\AccountCredit;" . PHP_EOL . "use App\\Models\\Plan;",
    $original
);

$original = str_replace(
    "use Illuminate\\Support\\Facades\\Log;",
    "use App\\Notifications\\SubscriptionChangedNotification;" . PHP_EOL . "use Illuminate\\Support\\Facades\\DB;" . PHP_EOL . "use Illuminate\\Support\\Facades\\Log;",
    $original
);

$original = str_replace(
    "private PaystackService \$paystackService" . PHP_EOL . "    ) {}",
    "private PaystackService \$paystackService," . PHP_EOL . "        private ProratedCalculationService \$proratedService" . PHP_EOL . "    ) {}",
    $original
);

echo "Step 1 & 2 completed\n";

// Step 3: Replace upgrade() method
$upgradeOld = <<<'OLD'
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
OLD;

$upgradeNew = <<<'NEW'
    /**
     * Upgrade a subscription to a higher plan (with payment required)
     */
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
NEW;

$original = str_replace($upgradeOld, $upgradeNew, $original);
echo "Step 3 completed\n";

file_put_contents('app/Services/SubscriptionService_new.php', $original);

echo "File written to app/Services/SubscriptionService_new.php\n";
echo "Testing syntax...\n";

$output = [];
$return = 0;
exec('php -l app/Services/SubscriptionService_new.php 2>&1', $output, $return);

if ($return === 0) {
    echo "✓ Syntax check passed!\n";
    echo "Now copying to main file...\n";
    copy('app/Services/SubscriptionService_new.php', 'app/Services/SubscriptionService.php');
    echo "✓ SubscriptionService updated successfully!\n";
} else {
    echo "✗ Syntax error:\n";
    echo implode("\n", $output) . "\n";
}
