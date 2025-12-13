<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'billing_cycle',
        'amount',
        'currency',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'canceled_at',
        'expires_at',
        'paystack_subscription_code',
        'paystack_email_token',
        'invoices_used',
        'customers_used',
        'sms_credits_used',
        'whatsapp_credits_used',
        'api_requests_used',
        'usage_reset_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'trial_ends_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'canceled_at' => 'datetime',
        'expires_at' => 'datetime',
        'usage_reset_at' => 'datetime',
    ];

    /**
     * Get the user that owns the subscription
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the plan for this subscription
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get usage records for this subscription
     */
    public function usageRecords(): HasMany
    {
        return $this->hasMany(UsageRecord::class);
    }

    /**
     * Get payment transactions for this subscription
     */
    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' &&
               ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * Check if subscription is on trial
     */
    public function isTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    /**
     * Check if subscription is expired
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired' ||
               ($this->expires_at !== null && $this->expires_at->isPast());
    }

    /**
     * Check if subscription is canceled
     */
    public function isCanceled(): bool
    {
        return $this->status === 'canceled';
    }

    /**
     * Check if subscription is past due
     */
    public function isPastDue(): bool
    {
        return $this->status === 'past_due';
    }

    /**
     * Get days until renewal
     */
    public function daysUntilRenewal(): int
    {
        if ($this->current_period_end === null) {
            return 0;
        }

        return max(0, now()->diffInDays($this->current_period_end, false));
    }

    /**
     * Increment usage counter
     */
    public function incrementUsage(string $type, int $quantity = 1): void
    {
        $field = match($type) {
            'invoice' => 'invoices_used',
            'customer' => 'customers_used',
            'sms' => 'sms_credits_used',
            'whatsapp' => 'whatsapp_credits_used',
            'api' => 'api_requests_used',
            default => null,
        };

        if ($field) {
            $this->increment($field, $quantity);
        }
    }

    /**
     * Reset monthly usage counters
     */
    public function resetUsage(): void
    {
        $this->update([
            'invoices_used' => 0,
            'customers_used' => 0,
            'sms_credits_used' => 0,
            'whatsapp_credits_used' => 0,
            'api_requests_used' => 0,
            'usage_reset_at' => now(),
        ]);
    }

    /**
     * Check if user has reached limit for specific type
     */
    public function hasReachedLimit(string $type): bool
    {
        if (!$this->plan) {
            return true;
        }

        return match($type) {
            'invoice' => !$this->plan->canCreateInvoice($this),
            'customer' => !$this->plan->canCreateCustomer($this),
            'sms' => !$this->plan->canSendSMS($this),
            'whatsapp' => !$this->plan->canSendWhatsApp($this),
            'api' => !$this->plan->canMakeApiRequest($this),
            default => false,
        };
    }

    /**
     * Cancel subscription
     */
    public function cancel(bool $immediately = false): void
    {
        if ($immediately) {
            $this->update([
                'status' => 'canceled',
                'canceled_at' => now(),
                'expires_at' => now(),
            ]);
        } else {
            $this->update([
                'status' => 'canceled',
                'canceled_at' => now(),
                'expires_at' => $this->current_period_end,
            ]);
        }
    }

    /**
     * Reactivate canceled subscription
     */
    public function reactivate(): void
    {
        $this->update([
            'status' => 'active',
            'canceled_at' => null,
            'expires_at' => null,
        ]);
    }

    /**
     * Mark subscription as expired
     */
    public function markAsExpired(): void
    {
        $this->update([
            'status' => 'expired',
            'expires_at' => now(),
        ]);
    }

    /**
     * Renew subscription for next period
     */
    public function renew(): void
    {
        $periodStart = $this->current_period_end ?? now();
        $periodEnd = $this->billing_cycle === 'yearly'
            ? $periodStart->copy()->addYear()
            : $periodStart->copy()->addMonth();

        $this->update([
            'status' => 'active',
            'current_period_start' => $periodStart,
            'current_period_end' => $periodEnd,
            'canceled_at' => null,
            'expires_at' => null,
        ]);

        $this->resetUsage();
    }

    /**
     * Get usage percentage for a specific type
     */
    public function getUsagePercentage(string $type): int
    {
        if (!$this->plan) {
            return 100;
        }

        $used = match($type) {
            'invoice' => $this->invoices_used,
            'customer' => $this->customers_used,
            'sms' => $this->sms_credits_used,
            'whatsapp' => $this->whatsapp_credits_used,
            'api' => $this->api_requests_used,
            default => 0,
        };

        $limit = match($type) {
            'invoice' => $this->plan->max_invoices,
            'customer' => $this->plan->max_customers,
            'sms' => $this->plan->sms_credits_monthly,
            'whatsapp' => $this->plan->whatsapp_credits_monthly,
            'api' => $this->plan->api_requests_monthly,
            default => 1,
        };

        if ($limit === -1) {
            return 0; // Unlimited
        }

        if ($limit === 0) {
            return 100;
        }

        return min(100, round(($used / $limit) * 100));
    }

    /**
     * Scope to get active subscriptions
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get expired subscriptions
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
                     ->orWhere(function($q) {
                         $q->whereNotNull('expires_at')
                           ->where('expires_at', '<', now());
                     });
    }
}
