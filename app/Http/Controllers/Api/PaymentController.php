<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\FeatureFlag;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Initialize payment for an invoice
     */
    public function initializePayment(Request $request, string $invoiceUuid)
    {
        try {
            if (!FeatureFlag::isEnabledForEnvironment('payment_orchestration')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment orchestration is not enabled',
                ], 403);
            }

            $invoice = Invoice::where('public_id', $invoiceUuid)->firstOrFail();

            $validated = $request->validate([
                'email' => 'sometimes|email',
                'phone' => 'sometimes|string',
                'name' => 'sometimes|string',
                'amount' => 'sometimes|numeric|min:0',
                'callback_url' => 'sometimes|url',
            ]);

            $result = $this->paymentService->initializePayment($invoice, $validated);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'payment_attempt_id' => $result['payment_attempt_id'],
                        'authorization_url' => $result['authorization_url'],
                        'reference' => $result['reference'],
                    ],
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Payment initialization failed', [
                'invoice_uuid' => $invoiceUuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while initializing payment',
            ], 500);
        }
    }

    /**
     * Verify payment
     */
    public function verifyPayment(Request $request, string $reference)
    {
        try {
            if (!FeatureFlag::isEnabledForEnvironment('payment_orchestration')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment orchestration is not enabled',
                ], 403);
            }

            $result = $this->paymentService->verifyPayment($reference);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'payment_attempt' => $result['payment_attempt'],
                        'invoice_payment' => $result['invoice_payment'] ?? null,
                    ],
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);

        } catch (\Exception $e) {
            Log::error('Payment verification failed', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while verifying payment',
            ], 500);
        }
    }

    /**
     * Get payment attempts for an invoice
     */
    public function getPaymentAttempts(string $invoiceUuid)
    {
        try {
            $invoice = Invoice::where('public_id', $invoiceUuid)
                ->with(['paymentAttempts' => function($query) {
                    $query->latest();
                }])
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $invoice->paymentAttempts,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to fetch payment attempts', [
                'invoice_uuid' => $invoiceUuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
            ], 500);
        }
    }
}
