<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function showPublic($publicId)
    {
        $invoice = Invoice::where('public_id', $publicId)
            ->with(['businessProfile', 'customer', 'items', 'user'])
            ->firstOrFail();

        // Get the invoice's business profile (with fallback to first profile if not set)
        $businessProfile = $invoice->businessProfile ?? $invoice->user->businessProfiles->first();

        // Allow viewing even without business profile (will show user name as fallback)
        return view('invoices.public', [
            'invoice' => $invoice,
            'businessProfile' => $businessProfile,
        ]);
    }

    public function downloadPdf($publicId)
    {
        $invoice = Invoice::where('public_id', $publicId)
            ->with(['businessProfile', 'customer', 'items', 'user'])
            ->firstOrFail();

        // Get the invoice's business profile (with fallback to first profile if not set)
        $businessProfile = $invoice->businessProfile ?? $invoice->user->businessProfiles->first();

        // Allow PDF generation even without business profile (will show user name as fallback)
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'businessProfile' => $businessProfile,
        ]);

        return $pdf->download("Invoice-{$invoice->invoice_number}.pdf");
    }
}
