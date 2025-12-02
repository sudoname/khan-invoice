<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Console\Command;

class RecalculateInvoiceTotals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:recalculate-totals';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate all invoice totals to fix double taxation issue';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Recalculating invoice totals...');

        // Step 1: Recalculate all invoice item line totals (without per-item tax)
        $this->info('Step 1: Recalculating invoice item line totals...');
        $items = InvoiceItem::all();
        $itemsUpdated = 0;

        foreach ($items as $item) {
            $oldTotal = $item->line_total;

            // Recalculate line total WITHOUT per-item tax
            $newTotal = ($item->quantity * $item->unit_price) - ($item->discount ?? 0);

            if ($oldTotal != $newTotal) {
                $item->line_total = $newTotal;
                $item->saveQuietly(); // Save without triggering observers yet
                $itemsUpdated++;

                $this->line("  Item ID {$item->id}: {$oldTotal} → {$newTotal}");
            }
        }

        $this->info("Updated {$itemsUpdated} invoice items.");

        // Step 2: Recalculate all invoice totals
        $this->info('Step 2: Recalculating invoice totals...');
        $invoices = Invoice::all();
        $invoicesUpdated = 0;

        foreach ($invoices as $invoice) {
            $oldTotal = $invoice->total_amount;

            // Recalculate using the observer logic
            $subTotal = $invoice->items()->sum('line_total');
            $discountTotal = $invoice->discount_total ?? 0;
            $afterDiscount = $subTotal - $discountTotal;

            $vatRate = $invoice->vat_rate ?? 0;
            $vatAmount = $afterDiscount * ($vatRate / 100);

            $whtRate = $invoice->wht_rate ?? 0;
            $whtAmount = $afterDiscount * ($whtRate / 100);

            $totalAmount = $afterDiscount + $vatAmount - $whtAmount;

            // Update invoice
            $invoice->sub_total = $subTotal;
            $invoice->vat_amount = $vatAmount;
            $invoice->wht_amount = $whtAmount;
            $invoice->total_amount = $totalAmount;
            $invoice->saveQuietly();

            if ($oldTotal != $totalAmount) {
                $invoicesUpdated++;
                $this->line("  Invoice {$invoice->invoice_number}: ₦{$oldTotal} → ₦{$totalAmount}");
            }
        }

        $this->info("Updated {$invoicesUpdated} invoices.");
        $this->info('Done! All invoice totals have been recalculated.');

        return Command::SUCCESS;
    }
}
