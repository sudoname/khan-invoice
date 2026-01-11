<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Customer;
use App\Models\InvoiceItem;
use App\Models\BusinessProfile;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuickInvoiceController extends Controller
{
    /**
     * Show the quick invoice creation form
     */
    public function create()
    {
        // Get user's last used business profile (if any)
        $lastBusinessProfile = BusinessProfile::where('user_id', auth()->id())
            ->latest('updated_at')
            ->first();

        // Get recent customers (last 10)
        $recentCustomers = Customer::where('user_id', auth()->id())
            ->latest('updated_at')
            ->limit(10)
            ->get();

        return view('invoices.quick-create', [
            'lastBusinessProfile' => $lastBusinessProfile,
            'recentCustomers' => $recentCustomers,
            'invoiceNumber' => Invoice::generateInvoiceNumber(),
        ]);
    }

    /**
     * Store the quick invoice
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_address' => 'nullable|string|max:500',
            'customer_id' => 'nullable|exists:customers,id',
            'business_profile_id' => 'nullable|exists:business_profiles,id',
            'invoice_number' => 'required|string|unique:invoices,invoice_number',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'simple_mode' => 'boolean',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'wht_rate' => 'nullable|numeric|min:0|max:100',
            'discount_total' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Find or create customer
            if ($request->customer_id) {
                $customer = Customer::findOrFail($request->customer_id);
            } else {
                $customer = Customer::firstOrCreate(
                    [
                        'user_id' => auth()->id(),
                        'email' => $validated['customer_email'],
                    ],
                    [
                        'name' => $validated['customer_name'],
                        'phone' => $validated['customer_phone'],
                        'address' => $validated['customer_address'],
                    ]
                );
            }

            // Calculate totals
            $subtotal = collect($validated['items'])->sum(function ($item) {
                return ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
            });

            $vatRate = $validated['simple_mode'] ?? false ? 0 : ($validated['vat_rate'] ?? 0);
            $whtRate = $validated['simple_mode'] ?? false ? 0 : ($validated['wht_rate'] ?? 0);
            $vatAmount = $subtotal * ($vatRate / 100);
            $whtAmount = $subtotal * ($whtRate / 100);
            $discountTotal = $validated['discount_total'] ?? 0;

            $totalAmount = $subtotal + $vatAmount - $whtAmount - $discountTotal;

            // Create invoice
            $invoice = Invoice::create([
                'user_id' => auth()->id(),
                'customer_id' => $customer->id,
                'business_profile_id' => $validated['business_profile_id'],
                'invoice_number' => $validated['invoice_number'],
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'status' => 'draft',
                'currency' => 'NGN',
                'simple_mode' => $validated['simple_mode'] ?? false,
                'sub_total' => $subtotal,
                'vat_rate' => $vatRate,
                'vat_amount' => $vatAmount,
                'wht_rate' => $whtRate,
                'wht_amount' => $whtAmount,
                'discount_total' => $discountTotal,
                'total_amount' => $totalAmount,
                'amount_due' => $totalAmount,
                'amount_paid' => 0,
                'notes' => $validated['notes'],
                'public_id' => Str::random(12),
            ]);

            // Create invoice items
            foreach ($validated['items'] as $itemData) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'discount' => $itemData['discount'] ?? 0,
                    'line_total' => ($itemData['quantity'] * $itemData['unit_price']) - ($itemData['discount'] ?? 0),
                ]);
            }

            DB::commit();

            // Track analytics (server-side)
            $analytics = app(AnalyticsService::class);
            $analytics->trackInvoiceCreated($invoice, 'quick');

            // Track time to first invoice if this is the user's first invoice
            if (auth()->user()->invoices()->count() === 1) {
                $analytics->trackTimeToFirstInvoice(auth()->user(), $invoice);
            }

            // Redirect to success with session data
            return redirect()->route('app.invoices.success', $invoice)
                ->with('invoice_created', true)
                ->with('context', 'quick')
                ->with('customer_type', $request->customer_id ? 'existing' : 'new');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create invoice: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Show the invoice success screen
     */
    public function success(Invoice $invoice)
    {
        // Ensure user owns this invoice
        if ($invoice->user_id !== auth()->id()) {
            abort(403);
        }

        return view('invoices.success', compact('invoice'));
    }
}
