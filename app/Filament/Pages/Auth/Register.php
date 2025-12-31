<?php

namespace App\Filament\Pages\Auth;

use App\Http\Controllers\InvoicePrefillController;
use App\Models\PublicInvoice;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Http\Request;

class Register extends BaseRegister
{
    protected static string $view = 'filament.pages.auth.register';

    public function mount(): void
    {
        parent::mount();

        // Check if user is coming from public invoice page
        $this->handlePrefillIntent();
    }

    protected function handlePrefillIntent(): void
    {
        $request = request();

        // Check if coming from public invoice
        if ($request->get('from') === 'public_invoice' && $request->has('invoice_id')) {
            $publicInvoiceId = $request->get('invoice_id');

            // Verify the invoice exists
            $publicInvoice = PublicInvoice::where('public_id', $publicInvoiceId)->first();

            if ($publicInvoice) {
                // Generate and store prefill token in session
                $token = InvoicePrefillController::generatePrefillToken($publicInvoiceId);
                session(['prefill_invoice_token' => $token]);
                session(['prefill_source' => 'public_invoice']);
                session(['prefill_public_invoice_id' => $publicInvoiceId]);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        // If there's a prefill intent, redirect to create invoice page
        if (session()->has('prefill_invoice_token')) {
            return route('filament.app.resources.invoices.create');
        }

        // Otherwise, redirect to default (dashboard)
        return parent::getRedirectUrl();
    }
}
