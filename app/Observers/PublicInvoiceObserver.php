<?php

namespace App\Observers;

use App\Jobs\InvoiceRehashJob;
use App\Models\PublicInvoice;

class PublicInvoiceObserver
{
    /**
     * Handle the PublicInvoice "created" event.
     */
    public function created(PublicInvoice $invoice): void
    {
        // Dispatch job to compute document hash
        InvoiceRehashJob::dispatch($invoice);
    }

    /**
     * Handle the PublicInvoice "updated" event.
     */
    public function updated(PublicInvoice $invoice): void
    {
        // Dispatch rehash job if hash-impacting fields changed
        if ($this->shouldRehash($invoice)) {
            InvoiceRehashJob::dispatch($invoice);
        }
    }

    /**
     * Determine if public invoice should be rehashed based on changed fields
     */
    protected function shouldRehash(PublicInvoice $invoice): bool
    {
        // Fields that impact the document hash for public invoices
        $hashImpactingFields = [
            'invoice_number',
            'issue_date',
            'due_date',
            'from_name',
            'from_email',
            'from_phone',
            'from_address',
            'from_bank_name',
            'from_account_number',
            'from_account_name',
            'from_account_type',
            'to_name',
            'to_email',
            'to_phone',
            'to_address',
            'items',
            'subtotal',
            'vat_percentage',
            'vat_amount',
            'wht_percentage',
            'wht_amount',
            'discount_percentage',
            'discount_amount',
            'total_amount',
            'notes',
        ];

        return $invoice->isDirty($hashImpactingFields);
    }
}
