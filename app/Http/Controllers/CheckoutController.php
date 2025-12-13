<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\PaymentTransaction;
use App\Services\PaystackService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    /**
     * Show checkout page
     */
    public function show(Request $request)
    {
        $planSlug = $request->input('plan');
        $billingCycle = $request->input('cycle', 'monthly');

        $plan = Plan::where('slug', $planSlug)
            ->where('is_active', true)
            ->firstOrFail();

        $amount = $billingCycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;

        return view('checkout', [
            'plan' => $plan,
            'billingCycle' => $billingCycle,
            'amount' => $amount,
        ]);
    }

    /**
     * Initialize Paystack payment
     */
    public function initializePayment(Request $request, PaystackService $paystackService)
    {
        $request->validate([
            'plan_slug' => 'required|string',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $plan = Plan::where('slug', $request->plan_slug)
            ->where('is_active', true)
            ->firstOrFail();

        $user = auth()->user();
        $billingCycle = $request->billing_cycle;
        $amount = $billingCycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;

        // Generate unique reference
        $reference = 'SUB_' . time() . '_' . $user->id;

        // Create pending transaction
        $transaction = PaymentTransaction::create([
            'user_id' => $user->id,
            'subscription_id' => $user->subscription?->id,
            'type' => 'subscription_payment',
            'status' => 'pending',
            'amount' => $amount,
            'currency' => 'NGN',
            'payment_gateway' => 'paystack',
            'transaction_reference' => $reference,
            'description' => "Subscription to {$plan->name} plan ({$billingCycle})",
            'metadata' => [
                'plan_slug' => $plan->slug,
                'billing_cycle' => $billingCycle,
            ],
        ]);

        // Initialize Paystack payment
        $result = $paystackService->initializeTransaction([
            'email' => $user->email,
            'amount' => $amount,
            'reference' => $reference,
            'callback_url' => route('checkout.verify'),
            'metadata' => [
                'plan_id' => $plan->id,
                'plan_slug' => $plan->slug,
                'billing_cycle' => $billingCycle,
                'user_id' => $user->id,
            ],
        ]);

        if ($result['status']) {
            return response()->json([
                'success' => true,
                'authorization_url' => $result['data']['authorization_url'],
                'access_code' => $result['data']['access_code'],
                'reference' => $reference,
            ]);
        }

        // Payment initialization failed
        $transaction->update(['status' => 'failed']);

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Failed to initialize payment',
        ], 400);
    }

    /**
     * Verify Paystack payment and activate subscription
     */
    public function verifyPayment(Request $request, PaystackService $paystackService, SubscriptionService $subscriptionService)
    {
        $reference = $request->input('reference');

        if (!$reference) {
            return redirect()->route('pricing')
                ->with('error', 'Invalid payment reference');
        }

        // Verify transaction with Paystack
        $result = $paystackService->verifyTransaction($reference);

        if (!$result['status']) {
            return redirect()->route('pricing')
                ->with('error', 'Payment verification failed');
        }

        $data = $result['data'];

        // Find transaction
        $transaction = PaymentTransaction::where('transaction_reference', $reference)->first();

        if (!$transaction) {
            return redirect()->route('pricing')
                ->with('error', 'Transaction not found');
        }

        // Check if payment was successful
        if ($data['status'] !== 'success') {
            $transaction->update([
                'status' => 'failed',
                'gateway_response' => json_encode($data),
            ]);

            return redirect()->route('pricing')
                ->with('error', 'Payment was not successful');
        }

        // Update transaction
        $transaction->update([
            'status' => 'successful',
            'paystack_reference' => $data['reference'],
            'gateway_response' => json_encode($data),
        ]);

        // Get plan from metadata
        $metadata = $transaction->metadata;
        $plan = Plan::where('slug', $metadata['plan_slug'])->first();

        if (!$plan) {
            return redirect()->route('pricing')
                ->with('error', 'Plan not found');
        }

        // Subscribe user to plan
        $subscriptionService->subscribe(
            $transaction->user,
            $plan,
            $metadata['billing_cycle']
        );

        return redirect()->route('filament.app.pages.my-subscription')
            ->with('success', "Successfully subscribed to {$plan->name} plan!");
    }
}
