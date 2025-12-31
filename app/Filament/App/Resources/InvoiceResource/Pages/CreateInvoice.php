<?php

namespace App\Filament\App\Resources\InvoiceResource\Pages;

use App\Filament\App\Resources\InvoiceResource;
use App\Http\Controllers\InvoicePrefillController;
use App\Models\PublicInvoice;
use App\Models\Customer;
use App\Models\BusinessProfile;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    public function mount(): void
    {
        parent::mount();

        // Check for prefill intent in session
        if (session()->has('prefill_invoice_token')) {
            $this->handlePrefill();
        }
    }

    protected function handlePrefill(): void
    {
        $token = session('prefill_invoice_token');
        $payload = InvoicePrefillController::validatePrefillToken($token);

        if (!$payload) {
            // Token invalid or expired
            session()->forget(['prefill_invoice_token', 'prefill_source']);
            Notification::make()
                ->warning()
                ->title('Prefill link expired')
                ->body('The invoice prefill link has expired. Please enter the details manually.')
                ->send();
            return;
        }

        // Get the public invoice
        $publicInvoice = PublicInvoice::where('public_id', $payload['public_invoice_id'])->first();

        if (!$publicInvoice) {
            session()->forget(['prefill_invoice_token', 'prefill_source']);
            return;
        }

        // Find or create customer
        $customer = null;
        if ($publicInvoice->customer_email) {
            $customer = Customer::where('user_id', auth()->id())
                ->where('email', $publicInvoice->customer_email)
                ->first();
        }

        if (!$customer && $publicInvoice->customer_name) {
            // Create new customer
            $customer = Customer::create([
                'user_id' => auth()->id(),
                'name' => $publicInvoice->customer_name,
                'email' => $publicInvoice->customer_email,
                'phone' => $publicInvoice->customer_phone,
                'address' => $publicInvoice->customer_address,
            ]);
        }

        // Find or create business profile
        $businessProfile = null;
        if ($publicInvoice->business_name) {
            $businessProfile = BusinessProfile::where('user_id', auth()->id())
                ->where('business_name', $publicInvoice->business_name)
                ->first();

            if (!$businessProfile) {
                // Create new business profile
                $businessProfile = BusinessProfile::create([
                    'user_id' => auth()->id(),
                    'business_name' => $publicInvoice->business_name,
                    'email' => $publicInvoice->business_email,
                    'phone' => $publicInvoice->business_phone,
                    'address' => $publicInvoice->business_address,
                ]);
            }
        }

        // Prefill the form data
        $prefillData = [
            'invoice_number' => \App\Models\Invoice::generateInvoiceNumber(), // Generate new number
            'issue_date' => $publicInvoice->issue_date ?? now(),
            'due_date' => $publicInvoice->due_date ?? now()->addDays(30),
            'status' => 'draft',
            'currency' => 'NGN',
            'notes' => $publicInvoice->notes,
            'footer' => $publicInvoice->footer_text,
        ];

        // Add customer if found/created
        if ($customer) {
            $prefillData['customer_id'] = $customer->id;
        }

        // Add business profile if found/created
        if ($businessProfile) {
            $prefillData['business_profile_id'] = $businessProfile->id;
        }

        // Prefill line items
        if ($publicInvoice->items && is_array($publicInvoice->items)) {
            $prefillData['items'] = collect($publicInvoice->items)->map(function ($item) {
                return [
                    'description' => $item['description'] ?? '',
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_price' => $item['unit_price'] ?? 0,
                    'discount' => $item['discount'] ?? 0,
                ];
            })->toArray();
        }

        // Apply prefill data to form
        $this->form->fill($prefillData);

        // Clear session
        session()->forget(['prefill_invoice_token', 'prefill_source']);

        // Show success notification
        Notification::make()
            ->success()
            ->title('Invoice prefilled!')
            ->body('Your invoice details have been imported. Review and save when ready.')
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
