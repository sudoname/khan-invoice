<?php

namespace App\Http\Controllers;

use App\Models\Payment\Payout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackTransferApprovalController extends Controller
{
    /**
     * Handle transfer approval requests from Paystack
     *
     * Paystack sends a POST request to this endpoint before processing each transfer
     * We can approve, decline, or modify the transfer details
     */
    public function approve(Request $request)
    {
        try {
            // Log the approval request
            Log::info('Paystack transfer approval request received', [
                'data' => $request->all(),
            ]);

            // Verify the request is from Paystack
            if (!$this->verifyPaystackSignature($request)) {
                Log::warning('Invalid Paystack signature on transfer approval request');
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid signature'
                ], 401);
            }

            // Get transfer details from Paystack
            $reference = $request->input('reference');
            $amount = $request->input('amount') / 100; // Convert from kobo to naira
            $recipient = $request->input('recipient');

            // Find the payout by reference
            $payout = Payout::where('reference', $reference)->first();

            if (!$payout) {
                Log::warning('Transfer approval request for unknown payout', [
                    'reference' => $reference,
                ]);

                // Decline unknown transfers
                return response()->json([
                    'status' => 'declined',
                    'message' => 'Payout not found in our system'
                ]);
            }

            // Verify the transfer details match our payout
            if ($payout->net_amount != $amount) {
                Log::error('Transfer amount mismatch', [
                    'reference' => $reference,
                    'expected' => $payout->net_amount,
                    'received' => $amount,
                ]);

                return response()->json([
                    'status' => 'declined',
                    'message' => 'Amount mismatch'
                ]);
            }

            // Check payout status
            if (!in_array($payout->status, ['PENDING', 'PROCESSING'])) {
                Log::warning('Transfer approval for payout with invalid status', [
                    'reference' => $reference,
                    'status' => $payout->status,
                ]);

                return response()->json([
                    'status' => 'declined',
                    'message' => 'Payout status is not valid for transfer'
                ]);
            }

            // If payout requires approval but hasn't been approved yet
            if ($payout->requires_approval && !$payout->approved_at) {
                Log::warning('Transfer approval requested for unapproved payout', [
                    'reference' => $reference,
                ]);

                return response()->json([
                    'status' => 'declined',
                    'message' => 'Payout not yet approved by admin'
                ]);
            }

            // All checks passed - approve the transfer
            Log::info('Transfer approved', [
                'reference' => $reference,
                'payout_id' => $payout->id,
                'amount' => $amount,
            ]);

            return response()->json([
                'status' => 'approved',
                'message' => 'Transfer approved'
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing transfer approval', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Decline on error to be safe
            return response()->json([
                'status' => 'declined',
                'message' => 'Error processing approval request'
            ], 500);
        }
    }

    /**
     * Verify that the request is from Paystack
     */
    protected function verifyPaystackSignature(Request $request): bool
    {
        $signature = $request->header('X-Paystack-Signature');

        if (!$signature) {
            return false;
        }

        $body = $request->getContent();
        $hash = hash_hmac('sha512', $body, config('services.paystack.secret_key'));

        return hash_equals($hash, $signature);
    }
}
