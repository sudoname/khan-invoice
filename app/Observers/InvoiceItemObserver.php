<?php

namespace App\Observers;

use App\Models\InvoiceItem;

class InvoiceItemObserver
{
    /**
     * Handle the InvoiceItem "created" event.
     */
    public function created(InvoiceItem $invoiceItem): void
    {
        $this->updateInvoiceTotals($invoiceItem);
    }

    /**
     * Handle the InvoiceItem "updated" event.
     */
    public function updated(InvoiceItem $invoiceItem): void
    {
        $this->updateInvoiceTotals($invoiceItem);
    }

    /**
     * Handle the InvoiceItem "deleted" event.
     */
    public function deleted(InvoiceItem $invoiceItem): void
    {
        $this->updateInvoiceTotals($invoiceItem);
    }

    /**
     * Handle the InvoiceItem "restored" event.
     */
    public function restored(InvoiceItem $invoiceItem): void
    {
        $this->updateInvoiceTotals($invoiceItem);
    }

    /**
     * Handle the InvoiceItem "force deleted" event.
     */
    public function forceDeleted(InvoiceItem $invoiceItem): void
    {
        $this->updateInvoiceTotals($invoiceItem);
    }

    /**
     * Update the invoice totals based on all its items
     */
    protected function updateInvoiceTotals(InvoiceItem $invoiceItem): void
    {
        $invoice = $invoiceItem->invoice;

        if (!$invoice) {
            return;
        }

        // Calculate subtotal from all items
        $subTotal = $invoice->items()->sum('line_total');

        // Apply invoice-level discount
        $discountTotal = $invoice->discount_total ?? 0;
        $afterDiscount = $subTotal - $discountTotal;

        // Calculate VAT
        $vatRate = $invoice->vat_rate ?? 0;
        $vatAmount = $afterDiscount * ($vatRate / 100);

        // Calculate WHT (deducted from total)
        $whtRate = $invoice->wht_rate ?? 0;
        $whtAmount = $afterDiscount * ($whtRate / 100);

        // Calculate final total
        $totalAmount = $afterDiscount + $vatAmount - $whtAmount;

        // Update invoice totals
        $invoice->sub_total = $subTotal;
        $invoice->vat_amount = $vatAmount;
        $invoice->wht_amount = $whtAmount;
        $invoice->total_amount = $totalAmount;

        $invoice->saveQuietly();
    }
}
