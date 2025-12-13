<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
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
        $query = Invoice::where('user_id', $request->user()->id)
            ->with(['customer', 'items']);

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
        $invoice = Invoice::where('user_id', $request->user()->id)
            ->with(['customer', 'items', 'payments'])
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
            'business_profile_id' => 'required|exists:business_profiles,id',
            'invoice_number' => 'nullable|string|unique:invoices',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'currency' => 'required|string|max:3',
            'status' => 'nullable|string|in:draft,sent,paid,partially_paid,overdue,cancelled',
            'sub_total' => 'required|numeric|min:0',
            'discount_total' => 'nullable|numeric|min:0',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'wht_rate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'footer' => 'nullable|string',
            'items' => 'nullable|array',
        ]);

        // Ensure customer belongs to the user
        $customer = $request->user()->customers()->findOrFail($validated['customer_id']);

        // Ensure business profile belongs to the user
        $businessProfile = $request->user()->businessProfiles()->findOrFail($validated['business_profile_id']);

        $validated['user_id'] = $request->user()->id;
        $validated['status'] = $validated['status'] ?? 'draft';

        // Calculate amounts
        $subTotal = $validated['sub_total'];
        $vatAmount = $subTotal * (($validated['vat_rate'] ?? 0) / 100);
        $whtAmount = $subTotal * (($validated['wht_rate'] ?? 0) / 100);
        $discountTotal = $validated['discount_total'] ?? 0;

        $validated['vat_amount'] = $vatAmount;
        $validated['wht_amount'] = $whtAmount;
        $validated['total_amount'] = $subTotal + $vatAmount - $whtAmount - $discountTotal;
        $validated['amount_paid'] = 0;

        $invoice = Invoice::create($validated);

        // Create items if provided
        if (isset($validated['items'])) {
            foreach ($validated['items'] as $item) {
                $invoice->items()->create($item);
            }
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
