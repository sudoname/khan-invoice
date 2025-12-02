<?php

namespace App\Http\Controllers;

use App\Models\PublicInvoice;
use App\Services\PaystackService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PublicInvoiceController extends Controller
{
    /**
     * Show the invoice generator form
     */
    public function create()
    {
        return view('public-invoice.create');
    }

    /**
     * Generate invoice preview and save to database
     */
    public function preview(Request $request)
    {
        $data = $this->validateAndPrepareData($request);

        // Handle logo upload if present
        $logoPath = null;
        if ($request->hasFile('company_logo')) {
            $logoPath = $request->file('company_logo')->store('company-logos', 'public');
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
        ]);

        // Redirect to the invoice show page
        return redirect()->route('public-invoice.show', $publicInvoice->public_id);
    }

    /**
     * Show a saved public invoice
     */
    public function show(string $publicId)
    {
        $invoice = PublicInvoice::where('public_id', $publicId)->firstOrFail();

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

                            $amountPaid = $data['amount'] / 100; // Convert from kobo to naira

                            // Update invoice payment details
                            $invoice->update([
                                'amount_paid' => ($invoice->amount_paid ?? 0) + $amountPaid,
                                'payment_status' => 'paid',
                                'paid_at' => now(),
                            ]);

                            Log::info('Public invoice payment processed: ' . $invoice->invoice_number, [
                                'reference' => $reference,
                                'amount' => $amountPaid,
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

        $validated['subtotal'] = $subtotal;
        $validated['vat_amount'] = $vat;
        $validated['wht_amount'] = $wht;
        $validated['discount_amount'] = $discount;
        $validated['total_amount'] = $total;

        return $validated;
    }
}
