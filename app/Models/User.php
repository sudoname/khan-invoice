<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail, FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'provider',
        'provider_id',
        'avatar',
        'role',
        'email_verified_at',
        'api_enabled',
        'api_rate_limit',
        'api_last_used_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'api_enabled' => 'boolean',
            'api_last_used_at' => 'datetime',
        ];
    }

    /**
     * Get the business profiles for the user.
     */
    public function businessProfiles(): HasMany
    {
        return $this->hasMany(BusinessProfile::class);
    }

    /**
     * Get the customers for the user.
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Get the invoices for the user.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the notification preferences for the user.
     */
    public function notificationPreferences(): HasOne
    {
        return $this->hasOne(NotificationPreference::class);
    }

    /**
     * Get the SMS logs for the user.
     */
    public function smsLogs(): HasMany
    {
        return $this->hasMany(SmsLog::class);
    }

    /**
     * Get the WhatsApp logs for the user.
     */
    public function whatsAppLogs(): HasMany
    {
        return $this->hasMany(WhatsAppLog::class);
    }

    /**
     * Get the subscription for the user.
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    /**
     * Get the payment transactions for the user.
     */
    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    /**
     * Get the usage records for the user.
     */
    public function usageRecords(): HasMany
    {
        return $this->hasMany(UsageRecord::class);
    }

    /**
     * Get the user's active subscription plan
     */
    public function plan(): ?Plan
    {
        return $this->subscription?->plan;
    }

    /**
     * Check if user has an active subscription
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscription && $this->subscription->isActive();
    }

    /**
     * Check if user can create invoice based on subscription limits
     */
    public function canCreateInvoice(): bool
    {
        if (!$this->subscription || !$this->subscription->plan) {
            return false;
        }

        return $this->subscription->plan->canCreateInvoice($this->subscription);
    }

    /**
     * Check if user can create customer based on subscription limits
     */
    public function canCreateCustomer(): bool
    {
        if (!$this->subscription || !$this->subscription->plan) {
            return false;
        }

        return $this->subscription->plan->canCreateCustomer($this->subscription);
    }

    /**
     * Check if user can send SMS based on subscription limits
     */
    public function canSendSMS(): bool
    {
        if (!$this->subscription || !$this->subscription->plan) {
            return false;
        }

        return $this->subscription->plan->canSendSMS($this->subscription);
    }

    /**
     * Check if user can send WhatsApp based on subscription limits
     */
    public function canSendWhatsApp(): bool
    {
        if (!$this->subscription || !$this->subscription->plan) {
            return false;
        }

        return $this->subscription->plan->canSendWhatsApp($this->subscription);
    }

    /**
     * Check if user can make API request based on subscription limits
     */
    public function canMakeApiRequest(): bool
    {
        if (!$this->subscription || !$this->subscription->plan) {
            return false;
        }

        return $this->subscription->plan->canMakeApiRequest($this->subscription);
    }

    /**
     * Check if user has specific feature in their plan
     */
    public function hasFeature(string $feature): bool
    {
        if (!$this->subscription || !$this->subscription->plan) {
            return false;
        }

        return $this->subscription->plan->hasFeature($feature);
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is a member.
     */
    public function isMember(): bool
    {
        return $this->role === 'member';
    }

    /**
     * Determine if the user can access the given panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Allow access to app panel for all authenticated users
        if ($panel->getId() === 'app') {
            return true;
        }

        // Allow access to admin panel only for admin users
        if ($panel->getId() === 'admin') {
            return $this->isAdmin();
        }

        return false;
    }
}
