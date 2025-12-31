<?php

namespace App\Http\Controllers;

use App\Models\PublicInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class InvoicePrefillController extends Controller
{
    /**
     * Generate a signed prefill URL for a public invoice
     *
     * @param string $publicInvoiceId
     * @return string Signed URL valid for 60 minutes
     */
    public static function generatePrefillUrl(string $publicInvoiceId): string
    {
        // Create signed URL that expires in 60 minutes
        return URL::temporarySignedRoute(
            'invoice.prefill',
            now()->addMinutes(60),
            ['invoice' => $publicInvoiceId]
        );
    }

    /**
     * Generate encrypted prefill token for session storage
     *
     * @param string $publicInvoiceId
     * @return string Encrypted token
     */
    public static function generatePrefillToken(string $publicInvoiceId): string
    {
        $payload = [
            'public_invoice_id' => $publicInvoiceId,
            'expires_at' => now()->addMinutes(60)->timestamp,
        ];

        return Crypt::encryptString(json_encode($payload));
    }

    /**
     * Validate and decrypt prefill token
     *
     * @param string $token
     * @return array|null Prefill data or null if invalid/expired
     */
    public static function validatePrefillToken(string $token): ?array
    {
        try {
            $decrypted = Crypt::decryptString($token);
            $payload = json_decode($decrypted, true);

            // Check if expired
            if (isset($payload['expires_at']) && $payload['expires_at'] < now()->timestamp) {
                return null;
            }

            return $payload;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get prefill data from public invoice
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPrefillData(Request $request)
    {
        // Validate signed URL
        if (!$request->hasValidSignature()) {
            return response()->json(['error' => 'Invalid or expired link'], 403);
        }

        $publicInvoiceId = $request->route('invoice');
        $publicInvoice = PublicInvoice::where('public_id', $publicInvoiceId)->first();

        if (!$publicInvoice) {
            return response()->json(['error' => 'Invoice not found'], 404);
        }

        // Return sanitized prefill data
        return response()->json([
            'success' => true,
            'data' => [
                'customer_name' => $publicInvoice->customer_name,
                'customer_email' => $publicInvoice->customer_email,
                'customer_phone' => $publicInvoice->customer_phone,
                'customer_address' => $publicInvoice->customer_address,
                'business_name' => $publicInvoice->business_name,
                'business_email' => $publicInvoice->business_email,
                'business_phone' => $publicInvoice->business_phone,
                'business_address' => $publicInvoice->business_address,
                'invoice_number' => $publicInvoice->invoice_number,
                'issue_date' => $publicInvoice->issue_date,
                'due_date' => $publicInvoice->due_date,
                'items' => $publicInvoice->items,
                'notes' => $publicInvoice->notes,
                'footer' => $publicInvoice->footer_text,
            ]
        ]);
    }

    /**
     * Handle prefill after user registration/login
     * Store prefill intent in session
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storePrefillIntent(Request $request)
    {
        $publicInvoiceId = $request->input('invoice_id');

        if (!$publicInvoiceId) {
            return redirect()->route('filament.app.auth.register');
        }

        // Verify the invoice exists
        $publicInvoice = PublicInvoice::where('public_id', $publicInvoiceId)->first();

        if (!$publicInvoice) {
            return redirect()->route('filament.app.auth.register')
                ->with('error', 'Invoice not found');
        }

        // Store prefill intent in session
        $token = self::generatePrefillToken($publicInvoiceId);
        session(['prefill_invoice_token' => $token]);
        session(['prefill_source' => 'public_invoice']);

        return redirect()->route('filament.app.auth.register')
            ->with('prefill_pending', true);
    }
}
