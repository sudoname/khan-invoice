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

    public function getAvailableCredits()
    {
        return \App\Models\AccountCredit::where('user_id', auth()->id())
            ->available()
            ->sum('amount');
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
                $result = $subscriptionService->upgrade($currentSubscription, $plan);

                if (!$result['success']) {
                    Notification::make()
                        ->title('Upgrade Failed')
                        ->body($result['message'] ?? 'Failed to process upgrade')
                        ->danger()
                        ->send();
                    return;
                }

                // If payment is required, redirect to payment
                if (isset($result['requires_payment']) && $result['requires_payment']) {
                    // Store upgrade info and redirect via JavaScript
                    $url = route('subscription.upgrade.initiate');
                    $this->dispatch('redirect-to-upgrade', [
                        'url' => $url,
                        'planSlug' => $plan->slug,
                        'amount' => $result['amount_to_pay'],
                        'credits' => $result['credits_available'] ?? 0
                    ]);
                    return;
                }

                // If no payment needed (covered by credits)
                Notification::make()
                    ->title('Plan Upgraded')
                    ->body("Successfully upgraded to {$plan->name} plan using your account credits!")
                    ->success()
                    ->send();

                return redirect()->route('filament.app.pages.my-subscription');

            } else {
                $result = $subscriptionService->downgrade($currentSubscription, $plan);

                if (!$result['success']) {
                    Notification::make()
                        ->title('Downgrade Failed')
                        ->body($result['message'] ?? 'Failed to process downgrade')
                        ->danger()
                        ->send();
                    return;
                }

                $creditIssued = $result['credit_issued'] ?? 0;
                $message = "Successfully downgraded to {$plan->name} plan!";
                if ($creditIssued > 0) {
                    $message .= " A credit of ₦" . number_format($creditIssued, 2) . " has been added to your account.";
                }

                Notification::make()
                    ->title('Plan Downgraded')
                    ->body($message)
                    ->success()
                    ->send();

                return redirect()->route('filament.app.pages.my-subscription');
            }

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
