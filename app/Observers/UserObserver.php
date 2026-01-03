<?php

namespace App\Observers;

use App\Models\User;
use App\Notifications\WelcomeNotification;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        //
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
