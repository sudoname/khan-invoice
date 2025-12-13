<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Invoice;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::query()
            ->whereHas('invoice', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            })
            ->with('invoice');

        if ($request->has('invoice_id')) {
            $query->where('invoice_id', $request->invoice_id);
        }

        $payments = $query->latest()
            ->paginate(min($request->get('per_page', 15), 100));

        return response()->json($payments);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string|max:50',
            'reference_number' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Ensure invoice belongs to the user
        $invoice = Invoice::where('user_id', $request->user()->id)
            ->findOrFail($validated['invoice_id']);

        $payment = Payment::create($validated);

        // Update invoice payment status
        $invoice->amount_paid += $validated['amount'];
        if ($invoice->amount_paid >= $invoice->total_amount) {
            $invoice->status = 'paid';
        } else {
            $invoice->status = 'partially_paid';
        }
        $invoice->save();

        return response()->json($payment->load('invoice'), 201);
    }
}
