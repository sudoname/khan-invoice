<?php

namespace App\Filament\App\Pages;

use App\Models\Plan;
use App\Services\SubscriptionService;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class SubscriptionPlans extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static string $view = 'filament.app.pages.subscription-plans';
    protected static ?string $navigationLabel = 'Subscription Plans';
    protected static ?string $title = 'Choose Your Plan';
    protected static ?string $navigationGroup = 'Billing';
    protected static ?int $navigationSort = 90;

    public function getPlans()
    {
        return Plan::active()->get();
    }

    public function getCurrentSubscription()
    {
        return auth()->user()->subscription;
    }

    public function selectPlan(int $planId, string $billingCycle = 'monthly')
    {
        $plan = Plan::findOrFail($planId);
        $user = auth()->user();
        $currentSubscription = $user->subscription;

        $subscriptionService = app(SubscriptionService::class);

        try {
            // If user has no subscription, create one
            if (!$currentSubscription) {
                $subscriptionService->subscribe($user, $plan, $billingCycle);

                Notification::make()
                    ->title('Subscription Created')
                    ->body("Successfully subscribed to {$plan->name} plan!")
                    ->success()
                    ->send();

                return redirect()->route('filament.app.pages.my-subscription');
            }

            // Check if can change plan
            $canChange = $subscriptionService->canChangePlan($currentSubscription, $plan);

            if (!$canChange['can_change']) {
                Notification::make()
                    ->title('Cannot Change Plan')
                    ->body($canChange['reason'])
                    ->warning()
                    ->send();

                return;
            }

            // Upgrade or downgrade
            if ($canChange['type'] === 'upgrade') {
                $subscriptionService->upgrade($currentSubscription, $plan);

                Notification::make()
                    ->title('Plan Upgraded')
                    ->body("Successfully upgraded to {$plan->name} plan!")
                    ->success()
                    ->send();
            } else {
                $subscriptionService->downgrade($currentSubscription, $plan);

                Notification::make()
                    ->title('Plan Changed')
                    ->body("Plan will be changed to {$plan->name} at the end of your current billing period.")
                    ->success()
                    ->send();
            }

            // Switch billing cycle if different
            if ($billingCycle !== $currentSubscription->billing_cycle) {
                $subscriptionService->switchBillingCycle($currentSubscription, $billingCycle);

                Notification::make()
                    ->title('Billing Cycle Updated')
                    ->body("Billing cycle changed to {$billingCycle}.")
                    ->info()
                    ->send();
            }

            return redirect()->route('filament.app.pages.my-subscription');

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('An error occurred while processing your request. Please try again.')
                ->danger()
                ->send();

            \Log::error('Subscription plan selection error', [
                'user_id' => $user->id,
                'plan_id' => $planId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
