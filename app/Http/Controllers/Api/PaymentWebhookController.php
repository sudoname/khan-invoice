<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\Payment\ProcessPaymentWebhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    /**
     * Handle Paystack webhook
     */
    public function handlePaystackWebhook(Request $request)
    {
        try {
            if (!$request->attributes->get('webhook_verified')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Webhook not verified',
                ], 401);
            }

            $payload = $request->all();

            Log::info('Paystack webhook received', [
                'event' => $payload['event'] ?? 'unknown',
                'reference' => $payload['data']['reference'] ?? null,
            ]);

            ProcessPaymentWebhook::dispatch('paystack', $payload);

            return response()->json([
                'status' => 'success',
                'message' => 'Webhook received',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Paystack webhook handling error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
            ], 500);
        }
    }

    /**
     * Handle Flutterwave webhook (for future)
     */
    public function handleFlutterwaveWebhook(Request $request)
    {
        try {
            if (!$request->attributes->get('webhook_verified')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Webhook not verified',
                ], 401);
            }

            $payload = $request->all();

            Log::info('Flutterwave webhook received', [
                'event' => $payload['event'] ?? 'unknown',
            ]);

            ProcessPaymentWebhook::dispatch('flutterwave', $payload);

            return response()->json([
                'status' => 'success',
                'message' => 'Webhook received',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Flutterwave webhook handling error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
            ], 500);
        }
    }
}
