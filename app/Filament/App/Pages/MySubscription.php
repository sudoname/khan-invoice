<?php

namespace App\Filament\App\Pages;

use App\Services\SubscriptionService;
use App\Services\UsageTracker;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class MySubscription extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static string $view = 'filament.app.pages.my-subscription';
    protected static ?string $navigationLabel = 'My Subscription';
    protected static ?string $title = 'Subscription & Usage';
    protected static ?string $navigationGroup = 'Billing';
    protected static ?int $navigationSort = 91;

    public function getSubscription()
    {
        return auth()->user()->subscription()->with('plan')->first();
    }

    public function getUsageSummary()
    {
        $usageTracker = app(UsageTracker::class);
        return $usageTracker->getUsageSummary(auth()->user());
    }

    public function cancelSubscription()
    {
        $subscription = $this->getSubscription();

        if (!$subscription) {
            Notification::make()
                ->title('No Active Subscription')
                ->body('You do not have an active subscription to cancel.')
                ->warning()
                ->send();

            return;
        }

        if ($subscription->isCanceled()) {
            Notification::make()
                ->title('Already Canceled')
                ->body('Your subscription is already canceled.')
                ->warning()
                ->send();

            return;
        }

        try {
            $subscriptionService = app(SubscriptionService::class);
            $subscriptionService->cancel($subscription, false); // Grace period

            Notification::make()
                ->title('Subscription Canceled')
                ->body('Your subscription has been canceled. You will continue to have access until the end of your billing period.')
                ->success()
                ->send();

            $this->dispatch('subscription-updated');

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('An error occurred while canceling your subscription. Please try again or contact support.')
                ->danger()
                ->send();

            \Log::error('Subscription cancellation error', [
                'user_id' => auth()->id(),
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function reactivateSubscription()
    {
        $subscription = $this->getSubscription();

        if (!$subscription || !$subscription->isCanceled()) {
            Notification::make()
                ->title('Cannot Reactivate')
                ->body('Only canceled subscriptions can be reactivated.')
                ->warning()
                ->send();

            return;
        }

        try {
            $subscriptionService = app(SubscriptionService::class);
            $subscriptionService->reactivate($subscription);

            Notification::make()
                ->title('Subscription Reactivated')
                ->body('Your subscription has been reactivated successfully!')
                ->success()
                ->send();

            $this->dispatch('subscription-updated');

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('An error occurred while reactivating your subscription. Please try again or contact support.')
                ->danger()
                ->send();

            \Log::error('Subscription reactivation error', [
                'user_id' => auth()->id(),
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function getPaymentTransactions()
    {
        return auth()->user()
            ->paymentTransactions()
            ->with('subscription')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }
}
