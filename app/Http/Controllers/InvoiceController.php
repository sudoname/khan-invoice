<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function showPublic($publicId)
    {
        $invoice = Invoice::where('public_id', $publicId)
            ->with(['businessProfile', 'customer', 'items'])
            ->firstOrFail();

        // Get the invoice's business profile (with fallback to first profile if not set)
        $businessProfile = $invoice->businessProfile ?? $invoice->user->businessProfiles->first();

        // Show 404 if no business profile is available
        if (!$businessProfile) {
            abort(404, 'Business profile not found');
        }

        return view('invoices.public', [
            'invoice' => $invoice,
            'businessProfile' => $businessProfile,
        ]);
    }

    public function downloadPdf($publicId)
    {
        $invoice = Invoice::where('public_id', $publicId)
            ->with(['businessProfile', 'customer', 'items'])
            ->firstOrFail();

        // Get the invoice's business profile (with fallback to first profile if not set)
        $businessProfile = $invoice->businessProfile ?? $invoice->user->businessProfiles->first();

        // Show 404 if no business profile is available
        if (!$businessProfile) {
            abort(404, 'Business profile not found');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'businessProfile' => $businessProfile,
        ]);

        return $pdf->download("Invoice-{$invoice->invoice_number}.pdf");
    }
}
