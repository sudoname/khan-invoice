<?php

namespace App\Observers;

use App\Models\Invoice;

class InvoiceObserver
{
    /**
     * Handle the Invoice "updating" event.
     * This fires BEFORE the model is saved to check if tax/discount fields changed
     */
    public function updating(Invoice $invoice): void
    {
        // Check if VAT rate, WHT rate, or discount total changed
        if ($invoice->isDirty(['vat_rate', 'wht_rate', 'discount_total'])) {
            $this->recalculateTotals($invoice);
        }
    }

    /**
     * Recalculate invoice totals based on current rates
     */
    protected function recalculateTotals(Invoice $invoice): void
    {
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
    }
}
