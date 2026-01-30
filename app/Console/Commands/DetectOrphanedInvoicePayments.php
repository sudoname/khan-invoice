<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Payment\InvoicePayment;
use Illuminate\Console\Command;

class DetectOrphanedInvoicePayments extends Command
{
    protected $signature = 'payments:detect-orphaned-invoices';
    protected $description = 'Detect invoices marked as paid but missing payment records';

    public function handle(): int
    {
        $this->info('Checking for orphaned invoice payments...');
        $this->newLine();

        // Find invoices marked as completed/processing but with no invoice_payment record
        $orphanedInvoices = Invoice::whereIn('payment_status', ['completed', 'processing'])
            ->where('amount_paid', '>', 0)
            ->whereDoesntHave('payments')
            ->get();

        if ($orphanedInvoices->isEmpty()) {
            $this->info('✅ No orphaned invoices found!');
            return Command::SUCCESS;
        }

        $this->error('🚨 ALERT! Found ' . $orphanedInvoices->count() . ' orphaned invoice(s) - Payments marked as received but not recorded in ledger!');
        $this->newLine();

        $table = [];
        foreach ($orphanedInvoices as $invoice) {
            $table[] = [
                'ID' => $invoice->id,
                'Invoice #' => $invoice->invoice_number,
                'User' => $invoice->user->name ?? 'N/A',
                'Amount Paid' => '₦' . number_format($invoice->amount_paid, 2),
                'Status' => $invoice->payment_status,
                'Date' => $invoice->created_at->format('Y-m-d H:i:s'),
            ];
        }

        $this->table(
            ['ID', 'Invoice #', 'User', 'Amount Paid', 'Status', 'Date'],
            $table
        );

        $this->newLine();
        $this->warn('These invoices are marked as paid but have no payment records!');
        $this->warn('They need to be backfilled using the backfill payment script.');

        return Command::FAILURE;
    }
}
