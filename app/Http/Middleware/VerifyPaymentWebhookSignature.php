<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\Payment\Providers\ProviderFactory;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyPaymentWebhookSignature
{
    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next, string $provider = 'paystack'): Response
    {
        try {
            $paymentProvider = ProviderFactory::make($provider);

            $signature = $request->header('X-Paystack-Signature') ??
                        $request->header('X-Flutterwave-Signature') ??
                        '';

            if (empty($signature)) {
                Log::warning('Webhook signature missing', [
                    'provider' => $provider,
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Signature verification failed',
                ], 401);
            }

            $payload = $request->getContent();

            if (!$paymentProvider->verifyWebhookSignature($payload, $signature)) {
                Log::warning('Webhook signature verification failed', [
                    'provider' => $provider,
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Signature verification failed',
                ], 401);
            }

            $request->attributes->set('webhook_verified', true);
            $request->attributes->set('webhook_provider', $provider);

            return $next($request);

        } catch (\Exception $e) {
            Log::error('Webhook signature verification error', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
            ], 500);
        }
    }
}
