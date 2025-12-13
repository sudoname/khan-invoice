<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_yearly',
        'currency',
        'max_invoices',
        'max_customers',
        'max_team_members',
        'sms_credits_monthly',
        'whatsapp_credits_monthly',
        'api_requests_monthly',
        'storage_gb',
        'multi_currency',
        'recurring_invoices',
        'api_access',
        'remove_branding',
        'white_label',
        'custom_domain',
        'priority_support',
        'advanced_reports',
        'is_active',
        'is_popular',
        'sort_order',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'storage_gb' => 'decimal:2',
        'multi_currency' => 'boolean',
        'recurring_invoices' => 'boolean',
        'api_access' => 'boolean',
        'remove_branding' => 'boolean',
        'white_label' => 'boolean',
        'custom_domain' => 'boolean',
        'priority_support' => 'boolean',
        'advanced_reports' => 'boolean',
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
    ];

    /**
     * Get subscriptions for this plan
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Check if user can create invoice based on plan limits
     */
    public function canCreateInvoice(Subscription $subscription): bool
    {
        if ($this->max_invoices === -1) {
            return true; // Unlimited
        }

        return $subscription->invoices_used < $this->max_invoices;
    }

    /**
     * Check if user can create customer based on plan limits
     */
    public function canCreateCustomer(Subscription $subscription): bool
    {
        if ($this->max_customers === -1) {
            return true; // Unlimited
        }

        return $subscription->customers_used < $this->max_customers;
    }

    /**
     * Check if user can send SMS
     */
    public function canSendSMS(Subscription $subscription): bool
    {
        return $subscription->sms_credits_used < $this->sms_credits_monthly;
    }

    /**
     * Check if user can send WhatsApp
     */
    public function canSendWhatsApp(Subscription $subscription): bool
    {
        return $subscription->whatsapp_credits_used < $this->whatsapp_credits_monthly;
    }

    /**
     * Check if user can make API request
     */
    public function canMakeApiRequest(Subscription $subscription): bool
    {
        if ($this->api_requests_monthly === 0 || !$this->api_access) {
            return false;
        }

        if ($this->api_requests_monthly === -1) {
            return true; // Unlimited
        }

        return $subscription->api_requests_used < $this->api_requests_monthly;
    }

    /**
     * Check if plan has specific feature
     */
    public function hasFeature(string $feature): bool
    {
        return $this->$feature ?? false;
    }

    /**
     * Get formatted monthly price
     */
    public function getFormattedMonthlyPriceAttribute(): string
    {
        return '₦' . number_format($this->price_monthly, 0);
    }

    /**
     * Get formatted yearly price
     */
    public function getFormattedYearlyPriceAttribute(): string
    {
        return '₦' . number_format($this->price_yearly, 0);
    }

    /**
     * Get yearly savings percentage
     */
    public function getYearlySavingsAttribute(): int
    {
        if ($this->price_monthly == 0 || $this->price_yearly == 0) {
            return 0;
        }

        $monthlyTotal = $this->price_monthly * 12;
        return round((($monthlyTotal - $this->price_yearly) / $monthlyTotal) * 100);
    }

    /**
     * Check if this is a free plan
     */
    public function isFree(): bool
    {
        return $this->slug === 'free' || $this->price_monthly == 0;
    }

    /**
     * Scope to get active plans
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
