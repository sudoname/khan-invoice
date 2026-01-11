<?php

namespace App\Http\Controllers;

use App\Models\AnalyticsEvent;
use App\Models\PublicInvoice;
use App\Services\AnalyticsService;
use App\Services\PaystackService;
use App\Services\PaystackSubaccountService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PublicInvoiceController extends Controller
{
    /**
     * Show the invoice generator form
     */
    public function create()
    {
        // Track deflection if user is authenticated
        if (auth()->check()) {
            $analytics = app(AnalyticsService::class);
            $analytics->track('auth_user_public_form_viewed', [
                'user_id_hash' => hash('sha256', auth()->id()),
                'user_has_invoices' => auth()->user()->invoices()->count() > 0,
                'user_invoice_count' => auth()->user()->invoices()->count(),
            ], null, auth()->id());
        }

        return view('public-invoice.create');
    }

    /**
     * Generate invoice preview and save to database
     */
    public function preview(Request $request)
    {
        try {
            $data = $this->validateAndPrepareData($request);
        } catch (\Exception $e) {
            // Track validation/generation error
            $this->trackEvent($request, 'invoice_generation_error', [
                'error_type' => 'validation',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }

        // Check for duplicate invoice in the last 5 minutes
        $fiveMinutesAgo = now()->subMinutes(5);
        $existingInvoice = PublicInvoice::where('from_name', $data['from_name'])
            ->where('to_name', $data['to_name'])
            ->where('total_amount', $data['total_amount'])
            ->where('created_at', '>=', $fiveMinutesAgo)
            ->first();

        if ($existingInvoice) {
            // Check if this is an API/AJAX request (from mobile app)
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => 'This invoice already exists. An invoice with the same details was created recently.',
                    'existing_invoice' => [
                        'public_id' => $existingInvoice->public_id,
                        'invoice_url' => route('public-invoice.show', $existingInvoice->public_id),
                        'created_at' => $existingInvoice->created_at->diffForHumans(),
                    ],
                ], 422);
            }

            // For web requests, redirect back with error
            return back()->withErrors([
                'duplicate' => 'This invoice already exists. An invoice with the same details was created ' .
                    $existingInvoice->created_at->diffForHumans() . '.'
            ])->withInput();
        }

        // Handle logo upload if present
        $logoPath = null;
        if ($request->hasFile('company_logo')) {
            $logoPath = $request->file('company_logo')->store('company-logos', 'public');
        }

        // Create Paystack subaccount if bank details are provided
        $subaccountCode = null;
        if (!empty($data['from_bank_name']) && !empty($data['from_account_number'])) {
            $subaccountService = new PaystackSubaccountService();

            // Get bank code from bank name
            $bankCode = $subaccountService->getBankCode($data['from_bank_name']);

            if ($bankCode) {
                $subaccountCode = $subaccountService->createSubaccount([
                    'business_name' => $data['from_name'],
                    'bank_code' => $bankCode,
                    'account_number' => $data['from_account_number'],
                    'description' => 'Public invoice merchant: ' . $data['from_name'],
                ]);

                if ($subaccountCode) {
                    Log::info('Subaccount created for public invoice', [
                        'subaccount_code' => $subaccountCode,
                        'business_name' => $data['from_name'],
                    ]);
                }
            }
        }

        // Create public invoice
        $publicInvoice = PublicInvoice::create([
            'public_id' => PublicInvoice::generatePublicId(),
            'invoice_number' => $data['invoice_number'],
            'from_name' => $data['from_name'],
            'from_email' => $data['from_email'] ?? null,
            'from_phone' => $data['from_phone'] ?? null,
            'from_address' => $data['from_address'] ?? null,
            'company_logo' => $logoPath,
            'from_bank_name' => $data['from_bank_name'] ?? null,
            'from_account_number' => $data['from_account_number'] ?? null,
            'from_account_name' => $data['from_account_name'] ?? null,
            'from_account_type' => $data['from_account_type'] ?? null,
            'paystack_subaccount_code' => $subaccountCode,
            'to_name' => $data['to_name'],
            'to_email' => $data['to_email'] ?? null,
            'to_phone' => $data['to_phone'] ?? null,
            'to_address' => $data['to_address'] ?? null,
            'issue_date' => $data['issue_date'],
            'due_date' => $data['due_date'],
            'items' => $data['items'],
            'subtotal' => $data['subtotal'],
            'vat_percentage' => $data['vat_percentage'] ?? 0,
            'vat_amount' => $data['vat_amount'],
            'wht_percentage' => $data['wht_percentage'] ?? 0,
            'wht_amount' => $data['wht_amount'],
            'discount_percentage' => $data['discount_percentage'] ?? 0,
            'discount_amount' => $data['discount_amount'],
            'total_amount' => $data['total_amount'],
            'notes' => $data['notes'] ?? null,
            'payment_status' => 'sent', // Invoice is sent when created
            'simple_mode' => $data['simple_mode'] ?? false,
        ]);

        // Track analytics event
        $invoiceMode = ($data['simple_mode'] ?? false) ? 'simple' : 'formal';
        $this->trackEvent($request, 'invoice_generated', [
            'mode' => $invoiceMode,
            'has_vat' => $publicInvoice->vat_percentage > 0,
            'has_wht' => $publicInvoice->wht_percentage > 0,
            'total_amount' => $publicInvoice->total_amount,
        ]);

        // Track deflection if authenticated user created a public invoice
        if (auth()->check()) {
            $analytics = app(AnalyticsService::class);
            $analytics->trackAuthUserDeflection(auth()->id(), $publicInvoice->public_id);
        }

        // Check if this is an API/AJAX request (from mobile app)
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            // Return JSON response for mobile app
            return response()->json([
                'success' => true,
                'message' => 'Invoice created successfully',
                'public_id' => $publicInvoice->public_id,
                'invoice_url' => route('public-invoice.show', $publicInvoice->public_id),
                'download_url' => route('public-invoice.download', $publicInvoice->public_id),
            ]);
        }

        // Set session flag for post-invoice conversion prompt
        session()->flash('invoice_just_created', true);

        // Redirect to the invoice show page (for web browsers)
        return redirect()->route('public-invoice.show', $publicInvoice->public_id);
    }

    /**
     * Show a saved public invoice
     */
    public function show(Request $request, string $publicId)
    {
        $invoice = PublicInvoice::where('public_id', $publicId)->firstOrFail();

        // Track invoice view (only if not just created in this session)
        if (!session('invoice_just_created')) {
            $this->trackEvent($request, 'invoice_viewed_shared', [
                'invoice_number' => $invoice->invoice_number,
                'payment_status' => $invoice->payment_status,
                'total_amount' => $invoice->total_amount,
            ]);
        }

        return view('public-invoice.show', compact('invoice'));
    }

    /**
     * Download invoice as PDF
     */
    public function download(string $publicId)
    {
        $invoice = PublicInvoice::where('public_id', $publicId)->firstOrFail();

        $pdf = Pdf::loadView('public-invoice.pdf', ['invoice' => $invoice]);

        $filename = 'invoice-' . $invoice->invoice_number . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Handle payment for a public invoice
     */
    public function pay(string $publicId)
    {
        $invoice = PublicInvoice::where('public_id', $publicId)->firstOrFail();

        // For now, just show the invoice with payment option
        // This will be enhanced with actual Paystack integration
        return view('public-invoice.show', compact('invoice'));
    }

    /**
     * Handle Paystack webhook for public invoices
     */
    public function webhook(Request $request)
    {
        // Log all webhook requests
        Log::info('Public invoice webhook received', [
            'event' => $request->input('event'),
            'reference' => $request->input('data.reference'),
            'headers' => $request->headers->all(),
        ]);

        // Verify Paystack signature
        $signature = $request->header('x-paystack-signature');

        if (!$signature) {
            Log::warning('Webhook missing signature');
            return response()->json(['message' => 'Missing signature'], 400);
        }

        $computedSignature = hash_hmac('sha512', $request->getContent(), config('services.paystack.secret_key'));
        if ($signature !== $computedSignature) {
            Log::warning('Webhook signature mismatch', [
                'received' => $signature,
                'computed' => $computedSignature,
            ]);
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $event = $request->input('event');
        $data = $request->input('data');

        try {
            Log::info('Processing webhook event', ['event' => $event]);

            if ($event === 'charge.success') {
                $reference = $data['reference'];
                Log::info('Processing charge.success', ['reference' => $reference]);

                // Check if this is a public invoice payment (reference starts with KI_PUBLIC_)
                if (str_starts_with($reference, 'KI_PUBLIC_')) {
                    // Extract public_id from reference: KI_PUBLIC_{publicId}_{timestamp}
                    preg_match('/KI_PUBLIC_(.+?)_/', $reference, $matches);

                    if (isset($matches[1])) {
                        $publicId = $matches[1];
                        Log::info('Extracted public_id', ['publicId' => $publicId]);

                        $invoice = PublicInvoice::where('public_id', $publicId)->first();

                        if ($invoice) {
                            // Check if invoice is already paid
                            if ($invoice->payment_status === 'paid') {
                                Log::warning('Duplicate payment attempt for already paid invoice', [
                                    'invoice_number' => $invoice->invoice_number,
                                    'reference' => $reference,
                                    'current_status' => $invoice->payment_status,
                                    'paid_at' => $invoice->paid_at,
                                ]);
                                // Don't process payment, but return success to avoid webhook retries
                                return response()->json(['message' => 'Invoice already paid'], 200);
                            }

                            $amountPaid = $data['amount'] / 100; // Convert from kobo to naira (customer paid amount)

                            // Calculate what business actually receives (after fees)
                            $netCalculation = \App\Models\PaymentSetting::calculateNetAmountReceived($amountPaid);

                            // Update invoice payment details
                            $invoice->update([
                                'amount_paid' => ($invoice->amount_paid ?? 0) + $amountPaid,
                                'payment_status' => 'paid',
                                'paid_at' => now(),
                            ]);

                            Log::info('Public invoice payment processed: ' . $invoice->invoice_number, [
                                'reference' => $reference,
                                'customer_paid' => $amountPaid,
                                'paystack_fee_deducted' => $netCalculation['paystack_fee'],
                                'service_charge_deducted' => $netCalculation['service_charge'],
                                'total_fees_deducted' => $netCalculation['total_fees'],
                                'net_amount_business_receives' => $netCalculation['net_amount_received'],
                                'fee_model' => 'business_absorbs_fees',
                                'receiver_bank' => $invoice->from_bank_name,
                                'receiver_account' => $invoice->from_account_number,
                                'receiver_account_name' => $invoice->from_account_name,
                                'metadata' => $data['metadata'] ?? [],
                            ]);
                        } else {
                            Log::warning('Invoice not found', ['publicId' => $publicId]);
                        }
                    } else {
                        Log::warning('Could not extract publicId from reference', ['reference' => $reference]);
                    }
                } else {
                    Log::info('Not a public invoice payment', ['reference' => $reference]);
                }
            }

            return response()->json(['message' => 'Webhook processed'], 200);

        } catch (\Exception $e) {
            Log::error('Public invoice webhook processing error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Error processing webhook'], 500);
        }
    }

    /**
     * Validate and prepare invoice data
     */
    private function validateAndPrepareData(Request $request)
    {
        $validated = $request->validate([
            // From (Business)
            'from_name' => 'required|string|max:255',
            'from_email' => 'nullable|email|max:255',
            'from_phone' => 'nullable|string|max:50',
            'from_address' => 'nullable|string|max:500',
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'from_bank_name' => 'nullable|string|max:255',
            'from_account_number' => 'nullable|string|max:50',
            'from_account_name' => 'nullable|string|max:255',
            'from_account_type' => 'nullable|string|max:50',

            // To (Customer)
            'to_name' => 'required|string|max:255',
            'to_email' => 'nullable|email|max:255',
            'to_phone' => 'nullable|string|max:50',
            'to_address' => 'nullable|string|max:500',

            // Invoice Details
            'invoice_number' => 'nullable|string|max:50',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',

            // Items
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',

            // Tax and Discount
            'vat_percentage' => 'nullable|numeric|min:0|max:100',
            'wht_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',

            // Notes
            'notes' => 'nullable|string|max:1000',
        ]);

        // Generate invoice number if not provided (INV-G + year + 5 random digits)
        if (empty($validated['invoice_number'])) {
            $validated['invoice_number'] = 'INV-G' . now()->year . str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
        }

        // Calculate totals
        $subtotal = 0;
        foreach ($validated['items'] as &$item) {
            $item['total'] = $item['quantity'] * $item['unit_price'];
            $subtotal += $item['total'];
        }

        $vat = $subtotal * ($validated['vat_percentage'] ?? 0) / 100;
        $wht = $subtotal * ($validated['wht_percentage'] ?? 0) / 100;
        $discount = $subtotal * ($validated['discount_percentage'] ?? 0) / 100;
        $total = $subtotal + $vat - $wht - $discount;

        // Validate that total amount is greater than 0
        if ($total <= 0) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'items' => ['The total invoice amount must be greater than ₦0.00. Please adjust your items, quantities, or discounts.']
            ]);
        }

        $validated['subtotal'] = $subtotal;
        $validated['vat_amount'] = $vat;
        $validated['wht_amount'] = $wht;
        $validated['discount_amount'] = $discount;
        $validated['total_amount'] = $total;

        return $validated;
    }

    /**
     * Mark invoice as sent (FREE-4)
     */
    public function markAsSent($publicId)
    {
        $invoice = PublicInvoice::where('public_id', $publicId)->firstOrFail();

        if ($invoice->markAsSent()) {
            // Fire analytics event
            Log::info('[Event] invoice_marked_sent', [
                'invoice_id' => $invoice->public_id,
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status,
            ]);

            return redirect()->back()->with('success', 'Invoice marked as sent successfully!');
        }

        return redirect()->back()->with('error', 'Unable to mark invoice as sent.');
    }

    /**
     * Track analytics event (server-side)
     */
    private function trackEvent(Request $request, string $eventName, array $properties = []): void
    {
        try {
            $ipHash = $request->ip() ? hash('sha256', $request->ip() . config('app.key')) : null;

            AnalyticsEvent::create([
                'name' => $eventName,
                'occurred_at' => now(),
                'path' => $request->path(),
                'referrer' => $request->header('referer'),
                'user_id' => auth()->id(),
                'properties' => $properties,
                'ip_hash' => $ipHash,
                'user_agent' => $request->userAgent(),
            ]);
        } catch (\Exception $e) {
            // Silently fail - analytics shouldn't break the user experience
            Log::debug('Analytics tracking failed', ['error' => $e->getMessage()]);
        }
    }
}
