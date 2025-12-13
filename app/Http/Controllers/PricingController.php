<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    /**
     * Display the pricing page
     */
    public function index()
    {
        $plans = Plan::active()->get();

        return view('pricing', [
            'plans' => $plans,
        ]);
    }

    /**
     * Handle plan selection (redirect to checkout or registration)
     */
    public function selectPlan(Request $request, $planSlug)
    {
        $plan = Plan::where('slug', $planSlug)->where('is_active', true)->firstOrFail();

        // Ensure cycle is always a valid string
        $billingCycle = $request->input('cycle') ?: 'monthly';
        if (!in_array($billingCycle, ['monthly', 'yearly'])) {
            $billingCycle = 'monthly';
        }

        // If user is not authenticated, redirect to registration with plan selection
        if (!auth()->check()) {
            return redirect()->route('register')->with([
                'selected_plan' => $plan->slug,
                'billing_cycle' => $billingCycle,
            ]);
        }

        // If free plan, subscribe immediately
        if ($plan->isFree()) {
            $subscriptionService = app(\App\Services\SubscriptionService::class);
            $subscriptionService->subscribe(auth()->user(), $plan, $billingCycle);

            return redirect()->route('filament.app.pages.my-subscription')
                ->with('success', 'Successfully subscribed to Free plan!');
        }

        // For paid plans, redirect to checkout
        return redirect()->route('checkout', [
            'plan' => $plan->slug,
            'cycle' => $billingCycle,
        ]);
    }
}
