<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Plan;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Get current subscription for the authenticated user.
     */
    public function current(Request $request)
    {
        $subscription = Subscription::where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->with(['plan'])
            ->first();

        if (!$subscription) {
            return response()->json([
                'message' => 'No active subscription found',
                'has_subscription' => false,
            ], 200);
        }

        return response()->json([
            'has_subscription' => true,
            'subscription' => [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'billing_cycle' => $subscription->billing_cycle,
                'amount' => (float) $subscription->amount,
                'currency' => $subscription->currency,
                'current_period_start' => $subscription->current_period_start?->format('Y-m-d'),
                'current_period_end' => $subscription->current_period_end?->format('Y-m-d'),
                'days_until_renewal' => $subscription->daysUntilRenewal(),
                'created_at' => $subscription->created_at->format('Y-m-d H:i:s'),
                'plan' => [
                    'id' => $subscription->plan->id,
                    'name' => $subscription->plan->name,
                    'slug' => $subscription->plan->slug,
                    'description' => $subscription->plan->description,
                    'features' => [
                        'max_invoices' => $subscription->plan->max_invoices,
                        'max_customers' => $subscription->plan->max_customers,
                        'sms_credits_monthly' => $subscription->plan->sms_credits_monthly,
                        'whatsapp_credits_monthly' => $subscription->plan->whatsapp_credits_monthly,
                        'api_access' => $subscription->plan->api_access,
                        'api_requests_monthly' => $subscription->plan->api_requests_monthly,
                        'multi_currency' => $subscription->plan->multi_currency,
                        'recurring_invoices' => $subscription->plan->recurring_invoices,
                        'priority_support' => $subscription->plan->priority_support,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Get all available subscription plans.
     */
    public function plans(Request $request)
    {
        $plans = Plan::where('is_active', true)
            ->orderBy('order')
            ->get()
            ->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'description' => $plan->description,
                    'is_popular' => $plan->is_popular,
                    'pricing' => [
                        'monthly_price' => (float) $plan->monthly_price,
                        'yearly_price' => (float) $plan->yearly_price,
                        'formatted_monthly_price' => $plan->formatted_monthly_price,
                        'formatted_yearly_price' => $plan->formatted_yearly_price,
                        'yearly_savings' => $plan->yearly_savings,
                        'currency' => $plan->currency,
                    ],
                    'features' => [
                        'max_invoices' => $plan->max_invoices === -1 ? 'unlimited' : $plan->max_invoices,
                        'max_customers' => $plan->max_customers === -1 ? 'unlimited' : $plan->max_customers,
                        'sms_credits_monthly' => $plan->sms_credits_monthly,
                        'whatsapp_credits_monthly' => $plan->whatsapp_credits_monthly,
                        'api_access' => $plan->api_access,
                        'api_requests_monthly' => $plan->api_requests_monthly,
                        'multi_currency' => $plan->multi_currency,
                        'recurring_invoices' => $plan->recurring_invoices,
                        'priority_support' => $plan->priority_support,
                    ],
                    'is_free' => $plan->isFree(),
                ];
            });

        return response()->json([
            'plans' => $plans,
        ]);
    }
}
