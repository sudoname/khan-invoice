<?php

namespace App\Services;

use App\Models\User;
use App\Models\UsageRecord;
use Illuminate\Support\Facades\Log;

class UsageTracker
{
    /**
     * Track invoice creation
     */
    public function trackInvoiceCreated(User $user, array $metadata = []): void
    {
        $subscription = $user->subscription;

        if (!$subscription) {
            Log::warning('No subscription found for invoice tracking', ['user_id' => $user->id]);
            return;
        }

        // Increment usage counter
        $subscription->incrementUsage('invoice');

        // Create usage record
        UsageRecord::create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'type' => 'invoice_created',
            'quantity' => 1,
            'description' => 'Invoice created',
            'metadata' => $metadata,
        ]);

        Log::info('Invoice creation tracked', [
            'user_id' => $user->id,
            'invoices_used' => $subscription->fresh()->invoices_used,
        ]);
    }

    /**
     * Track customer creation
     */
    public function trackCustomerCreated(User $user, array $metadata = []): void
    {
        $subscription = $user->subscription;

        if (!$subscription) {
            Log::warning('No subscription found for customer tracking', ['user_id' => $user->id]);
            return;
        }

        // Increment usage counter
        $subscription->incrementUsage('customer');

        // Create usage record
        UsageRecord::create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'type' => 'customer_created',
            'quantity' => 1,
            'description' => 'Customer created',
            'metadata' => $metadata,
        ]);

        Log::info('Customer creation tracked', [
            'user_id' => $user->id,
            'customers_used' => $subscription->fresh()->customers_used,
        ]);
    }

    /**
     * Track SMS sent
     */
    public function trackSmsSent(User $user, int $quantity = 1, array $metadata = []): void
    {
        $subscription = $user->subscription;

        if (!$subscription) {
            Log::warning('No subscription found for SMS tracking', ['user_id' => $user->id]);
            return;
        }

        // Increment usage counter
        $subscription->incrementUsage('sms', $quantity);

        // Create usage record
        UsageRecord::create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'type' => 'sms_sent',
            'quantity' => $quantity,
            'description' => "SMS sent ($quantity messages)",
            'metadata' => $metadata,
        ]);

        Log::info('SMS usage tracked', [
            'user_id' => $user->id,
            'quantity' => $quantity,
            'sms_credits_used' => $subscription->fresh()->sms_credits_used,
        ]);
    }

    /**
     * Track WhatsApp message sent
     */
    public function trackWhatsAppSent(User $user, int $quantity = 1, array $metadata = []): void
    {
        $subscription = $user->subscription;

        if (!$subscription) {
            Log::warning('No subscription found for WhatsApp tracking', ['user_id' => $user->id]);
            return;
        }

        // Increment usage counter
        $subscription->incrementUsage('whatsapp', $quantity);

        // Create usage record
        UsageRecord::create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'type' => 'whatsapp_sent',
            'quantity' => $quantity,
            'description' => "WhatsApp sent ($quantity messages)",
            'metadata' => $metadata,
        ]);

        Log::info('WhatsApp usage tracked', [
            'user_id' => $user->id,
            'quantity' => $quantity,
            'whatsapp_credits_used' => $subscription->fresh()->whatsapp_credits_used,
        ]);
    }

    /**
     * Track API request
     */
    public function trackApiRequest(User $user, array $metadata = []): void
    {
        $subscription = $user->subscription;

        if (!$subscription) {
            Log::warning('No subscription found for API tracking', ['user_id' => $user->id]);
            return;
        }

        // Increment usage counter
        $subscription->incrementUsage('api');

        // Create usage record
        UsageRecord::create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'type' => 'api_request',
            'quantity' => 1,
            'description' => 'API request made',
            'metadata' => $metadata,
        ]);

        // Update last used timestamp
        $user->update(['api_last_used_at' => now()]);
    }

    /**
     * Check if user can perform a specific action
     */
    public function canPerformAction(User $user, string $action): bool
    {
        // Admin users bypass all limits
        if ($user->isAdmin()) {
            return true;
        }

        // Check if user has active subscription
        if (!$user->hasActiveSubscription()) {
            return false;
        }

        // Delegate to user's helper methods
        return match($action) {
            'create_invoice' => $user->canCreateInvoice(),
            'create_customer' => $user->canCreateCustomer(),
            'send_sms' => $user->canSendSMS(),
            'send_whatsapp' => $user->canSendWhatsApp(),
            'make_api_request' => $user->canMakeApiRequest(),
            default => false,
        };
    }

    /**
     * Get usage summary for user
     */
    public function getUsageSummary(User $user): array
    {
        $subscription = $user->subscription;

        if (!$subscription || !$subscription->plan) {
            return [
                'has_subscription' => false,
                'message' => 'No active subscription',
            ];
        }

        $plan = $subscription->plan;

        return [
            'has_subscription' => true,
            'plan_name' => $plan->name,
            'billing_cycle' => $subscription->billing_cycle,
            'period_end' => $subscription->current_period_end?->format('Y-m-d'),
            'usage' => [
                'invoices' => [
                    'used' => $subscription->invoices_used,
                    'limit' => $plan->max_invoices,
                    'percentage' => $subscription->getUsagePercentage('invoice'),
                    'unlimited' => $plan->max_invoices === -1,
                ],
                'customers' => [
                    'used' => $subscription->customers_used,
                    'limit' => $plan->max_customers,
                    'percentage' => $subscription->getUsagePercentage('customer'),
                    'unlimited' => $plan->max_customers === -1,
                ],
                'sms' => [
                    'used' => $subscription->sms_credits_used,
                    'limit' => $plan->sms_credits_monthly,
                    'percentage' => $subscription->getUsagePercentage('sms'),
                    'unlimited' => $plan->sms_credits_monthly === -1,
                ],
                'whatsapp' => [
                    'used' => $subscription->whatsapp_credits_used,
                    'limit' => $plan->whatsapp_credits_monthly,
                    'percentage' => $subscription->getUsagePercentage('whatsapp'),
                    'unlimited' => $plan->whatsapp_credits_monthly === -1,
                ],
                'api' => [
                    'used' => $subscription->api_requests_used,
                    'limit' => $plan->api_requests_monthly,
                    'percentage' => $subscription->getUsagePercentage('api'),
                    'unlimited' => $plan->api_requests_monthly === -1,
                ],
            ],
        ];
    }

    /**
     * Get usage records for user (with optional filtering)
     */
    public function getUsageRecords(User $user, ?string $type = null, ?int $limit = 50): array
    {
        $query = UsageRecord::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        if ($type) {
            $query->byType($type);
        }

        $records = $query->limit($limit)->get();

        return $records->map(function ($record) {
            return [
                'type' => $record->type,
                'formatted_type' => $record->formatted_type,
                'quantity' => $record->quantity,
                'description' => $record->description,
                'created_at' => $record->created_at->format('Y-m-d H:i:s'),
                'metadata' => $record->metadata,
            ];
        })->toArray();
    }

    /**
     * Check if user is approaching limit for a specific type
     */
    public function isApproachingLimit(User $user, string $type, int $threshold = 80): bool
    {
        $subscription = $user->subscription;

        if (!$subscription || !$subscription->plan) {
            return false;
        }

        $percentage = $subscription->getUsagePercentage($type);

        return $percentage >= $threshold;
    }
}
