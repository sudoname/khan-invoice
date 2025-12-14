<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\PaymentTransaction;
use App\Services\PaystackService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubscriptionUpgradeController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private PaystackService $paystackService
    ) {}

    public function initiate(Request $request)
    {
        $request->validate(['plan_slug' => 'required|exists:plans,slug']);

        $user = auth()->user();
        $subscription = $user->subscription;

        if (!$subscription) {
            return response()->json(['success' => false, 'message' => 'No active subscription found'], 400);
        }

        $newPlan = Plan::where('slug', $request->plan_slug)->firstOrFail();
        $canChange = $this->subscriptionService->canChangePlan($subscription, $newPlan);

        if (!$canChange['can_change']) {
            return response()->json(['success' => false, 'message' => $canChange['reason']], 400);
        }

        $result = $this->subscriptionService->upgrade($subscription, $newPlan);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        if (!isset($result['requires_payment']) || !$result['requires_payment']) {
            return response()->json([
                'success' => true,
                'message' => 'Subscription upgraded successfully using credits',
                'redirect' => route('filament.app.pages.my-subscription'),
            ]);
        }

        $transaction = PaymentTransaction::create([
            'user_id' => $user->id,
            'type' => 'subscription_upgrade',
            'amount' => $result['amount_to_pay'],
            'currency' => 'NGN',
            'status' => 'pending',
            'metadata' => [
                'plan_id' => $newPlan->id,
                'subscription_id' => $subscription->id,
                'original_amount' => $result['original_amount'],
                'credits_applied' => $result['credits_available'],
            ],
        ]);

        $paystackResponse = $this->paystackService->initializeTransaction(
            email: $user->email,
            amount: $result['amount_to_pay'],
            reference: $transaction->reference,
            callbackUrl: route('subscription.upgrade.verify', ['reference' => $transaction->reference]),
            metadata: [
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'plan_slug' => $newPlan->slug,
                'type' => 'subscription_upgrade',
            ]
        );

        if (!$paystackResponse['status']) {
            $transaction->update(['status' => 'failed']);
            return response()->json(['success' => false, 'message' => 'Failed to initialize payment: ' . $paystackResponse['message']], 500);
        }

        $transaction->update(['paystack_reference' => $paystackResponse['data']['reference']]);

        return response()->json([
            'success' => true,
            'authorization_url' => $paystackResponse['data']['authorization_url'],
            'message' => 'Redirecting to payment page',
        ]);
    }

    public function verify(Request $request)
    {
        $reference = $request->query('reference');

        if (!$reference) {
            return redirect()->route('filament.app.pages.my-subscription')->with('error', 'Invalid payment reference');
        }

        $transaction = PaymentTransaction::where('reference', $reference)->first();

        if (!$transaction) {
            return redirect()->route('filament.app.pages.my-subscription')->with('error', 'Transaction not found');
        }

        $verifyResponse = $this->paystackService->verifyTransaction($reference);

        if (!$verifyResponse['status']) {
            $transaction->update(['status' => 'failed']);
            return redirect()->route('filament.app.pages.my-subscription')->with('error', 'Payment verification failed');
        }

        $paymentData = $verifyResponse['data'];

        if ($paymentData['status'] !== 'success') {
            $transaction->update(['status' => 'failed']);
            return redirect()->route('filament.app.pages.my-subscription')->with('error', 'Payment was not successful');
        }

        $transaction->update([
            'status' => 'successful',
            'paystack_reference' => $paymentData['reference'],
            'paid_at' => now(),
        ]);

        $user = $transaction->user;
        $subscription = $user->subscription;
        $newPlan = Plan::find($transaction->metadata['plan_id']);

        if (!$subscription || !$newPlan) {
            Log::error('Subscription or plan not found', ['transaction_id' => $transaction->id, 'user_id' => $user->id]);
            return redirect()->route('filament.app.pages.my-subscription')->with('error', 'Failed to complete upgrade');
        }

        $upgradeResult = $this->subscriptionService->completeUpgrade(
            subscription: $subscription,
            newPlan: $newPlan,
            totalUpgradeAmount: $transaction->amount,
            creditsApplied: $transaction->metadata['credits_applied'] ?? 0,
            transactionId: $transaction->id
        );

        if ($upgradeResult['success']) {
            return redirect()->route('filament.app.pages.my-subscription')->with('success', 'Subscription upgraded successfully!');
        }

        return redirect()->route('filament.app.pages.my-subscription')->with('error', 'Failed to complete upgrade');
    }

    public function downgrade(Request $request)
    {
        $request->validate(['plan_slug' => 'required|exists:plans,slug']);

        $user = auth()->user();
        $subscription = $user->subscription;

        if (!$subscription) {
            return response()->json(['success' => false, 'message' => 'No active subscription found'], 400);
        }

        $newPlan = Plan::where('slug', $request->plan_slug)->firstOrFail();
        $canChange = $this->subscriptionService->canChangePlan($subscription, $newPlan);

        if (!$canChange['can_change']) {
            return response()->json(['success' => false, 'message' => $canChange['reason']], 400);
        }

        $result = $this->subscriptionService->downgrade($subscription, $newPlan);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'credit_issued' => $result['credit_issued'] ?? 0,
                'redirect' => route('filament.app.pages.my-subscription'),
            ]);
        }

        return response()->json($result, 400);
    }
}
