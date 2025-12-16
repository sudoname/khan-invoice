<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\InvoiceResource;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    /**
     * Display a listing of invoices for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Invoice::query();

        // Admin users see all invoices, regular users see only their own
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        $query->with(['customer', 'items', 'businessProfile']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('issue_date', [$request->start_date, $request->end_date]);
        }

        $perPage = min($request->get('per_page', 15), 100);
        $invoices = $query->latest()->paginate($perPage);

        return InvoiceResource::collection($invoices);
    }

    /**
     * Display the specified invoice.
     */
    public function show(Request $request, string $id)
    {
        $query = Invoice::query();

        // Admin users can view all invoices, regular users only their own
        if (!$request->user()->isAdmin()) {
            $query->where('user_id', $request->user()->id);
        }

        $invoice = $query->with(['customer', 'items', 'payments', 'businessProfile'])
            ->findOrFail($id);

        return new InvoiceResource($invoice);
    }

    /**
     * Store a newly created invoice.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'business_profile_id' => 'nullable|exists:business_profiles,id',
            'invoice_number' => 'nullable|string|unique:invoices',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'currency' => 'nullable|string|max:3',
            'status' => 'nullable|string|in:draft,sent,paid,partially_paid,overdue,cancelled',
            'sub_total' => 'nullable|numeric|min:0',
            'discount_total' => 'nullable|numeric|min:0',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'wht_rate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'footer' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        // Ensure customer belongs to the user
        $customer = $request->user()->customers()->findOrFail($validated['customer_id']);

        // Get business profile (use provided one or user's first profile)
        if (isset($validated['business_profile_id'])) {
            $businessProfile = $request->user()->businessProfiles()->findOrFail($validated['business_profile_id']);
        } else {
            $businessProfile = $request->user()->businessProfiles()->first();
            if (!$businessProfile) {
                return response()->json([
                    'message' => 'Please create a business profile first',
                    'errors' => ['business_profile' => ['No business profile found. Please create one in settings.']]
                ], 422);
            }
            $validated['business_profile_id'] = $businessProfile->id;
        }

        $validated['user_id'] = $request->user()->id;
        $validated['status'] = $validated['status'] ?? 'draft';
        $validated['currency'] = $validated['currency'] ?? $businessProfile->default_currency ?? 'NGN';

        // Calculate sub_total from items if not provided
        if (!isset($validated['sub_total']) || $validated['sub_total'] == 0) {
            $subTotal = 0;
            foreach ($validated['items'] as $item) {
                $total = $item['quantity'] * $item['unit_price'];
                $item['total'] = $total;
                $subTotal += $total;
            }
            $validated['sub_total'] = $subTotal;
        } else {
            $subTotal = $validated['sub_total'];
        }

        // Calculate amounts
        $vatAmount = $subTotal * (($validated['vat_rate'] ?? 0) / 100);
        $whtAmount = $subTotal * (($validated['wht_rate'] ?? 0) / 100);
        $discountTotal = $validated['discount_total'] ?? 0;

        $validated['vat_amount'] = $vatAmount;
        $validated['wht_amount'] = $whtAmount;
        $validated['total_amount'] = $subTotal + $vatAmount - $whtAmount - $discountTotal;
        $validated['amount_paid'] = 0;

        $invoice = Invoice::create($validated);

        // Create items
        foreach ($validated['items'] as $item) {
            $invoice->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => $item['quantity'] * $item['unit_price'],
            ]);
        }

        Log::info('Invoice created via API', [
            'user_id' => $request->user()->id,
            'invoice_id' => $invoice->id,
        ]);

        return new InvoiceResource($invoice->load(['customer', 'items']));
    }

    /**
     * Update the specified invoice.
     */
    public function update(Request $request, string $id)
    {
        $invoice = Invoice::where('user_id', $request->user()->id)->findOrFail($id);

        $validated = $request->validate([
            'customer_id' => 'exists:customers,id',
            'issue_date' => 'date',
            'due_date' => 'date|after_or_equal:issue_date',
            'status' => 'string|in:draft,sent,paid,partially_paid,overdue,cancelled',
            'notes' => 'nullable|string',
            'footer' => 'nullable|string',
        ]);

        // Ensure customer belongs to the user if provided
        if (isset($validated['customer_id'])) {
            $request->user()->customers()->findOrFail($validated['customer_id']);
        }

        $invoice->update($validated);

        Log::info('Invoice updated via API', [
            'user_id' => $request->user()->id,
            'invoice_id' => $invoice->id,
        ]);

        return new InvoiceResource($invoice->load(['customer', 'items', 'payments']));
    }

    /**
     * Remove the specified invoice.
     */
    public function destroy(Request $request, string $id)
    {
        $invoice = Invoice::where('user_id', $request->user()->id)->findOrFail($id);

        $invoice->delete();

        Log::info('Invoice deleted via API', [
            'user_id' => $request->user()->id,
            'invoice_id' => $id,
        ]);

        return response()->json([
            'message' => 'Invoice deleted successfully',
        ]);
    }
}
