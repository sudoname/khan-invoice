<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Notifications\WelcomeNotification;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Automatically create a Professional subscription with 60-day trial
        $this->createTrialSubscription($user);
    }

    /**
     * Create a Professional plan subscription with 60-day trial
     */
    protected function createTrialSubscription(User $user): void
    {
        // Get the Professional plan (ID 3)
        $professionalPlan = Plan::find(3);

        if (!$professionalPlan) {
            // Fallback to first active plan if Professional doesn't exist
            $professionalPlan = Plan::active()->first();
        }

        if (!$professionalPlan) {
            return; // No plans available
        }

        // Check if user already has a subscription (prevent duplicates)
        if ($user->subscriptions()->exists()) {
            return;
        }

        $trialEnds = now()->addDays(60);
        $periodStart = now();
        $periodEnd = now()->addMonth(); // Monthly billing after trial

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $professionalPlan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'amount' => $professionalPlan->price_monthly,
            'currency' => 'NGN',
            'trial_ends_at' => $trialEnds,
            'current_period_start' => $periodStart,
            'current_period_end' => $periodEnd,
            'usage_reset_at' => now(),
            'invoices_used' => 0,
            'customers_used' => 0,
            'sms_credits_used' => 0,
            'whatsapp_credits_used' => 0,
            'api_requests_used' => 0,
        ]);
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // When user verifies their email for the first time
        if ($user->wasChanged('email_verified_at') && $user->email_verified_at !== null) {
            // Enable API access
            if (!$user->api_enabled) {
                $user->updateQuietly(['api_enabled' => true]);
            }

            // Send welcome email
            $user->notify(new WelcomeNotification($user));
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
