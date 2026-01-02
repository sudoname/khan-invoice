<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Console\Command;

class BackfillReceivedInvoiceExpenses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:backfill-expenses {--dry-run : Show what would be created without creating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create expense records for existing invoices where the customer is a registered user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Backfilling expenses for received invoices...');
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No records will be created');
        }

        // Get all invoices where customer has a matching user account
        // and no expense record exists yet
        $invoices = Invoice::whereHas('customer', function ($query) {
            $query->whereNotNull('email');
        })
        ->whereDoesntHave('expenses') // No expense record exists
        ->with(['customer', 'user', 'businessProfile'])
        ->get();

        $this->info("Found {$invoices->count()} invoices to process");

        $created = 0;
        $skipped = 0;

        foreach ($invoices as $invoice) {
            // Find user by customer email
            $recipientUser = User::where('email', $invoice->customer->email)->first();

            if (!$recipientUser) {
                $this->line("  Skip: {$invoice->invoice_number} - Customer {$invoice->customer->email} is not a registered user");
                $skipped++;
                continue;
            }

            // Don't create expense for yourself
            if ($recipientUser->id === $invoice->user_id) {
                $this->line("  Skip: {$invoice->invoice_number} - Self-invoice");
                $skipped++;
                continue;
            }

            // Check if expense already exists
            $existingExpense = Expense::where('received_invoice_id', $invoice->id)->first();
            if ($existingExpense) {
                $this->line("  Skip: {$invoice->invoice_number} - Expense already exists");
                $skipped++;
                continue;
            }

            $expenseStatus = match($invoice->status) {
                'paid' => 'paid',
                'cancelled' => 'cancelled',
                default => 'pending'
            };

            $this->line("  Creating expense for {$invoice->invoice_number} → User: {$recipientUser->name} (₦" . number_format($invoice->total_amount, 2) . ")");

            if (!$isDryRun) {
                Expense::create([
                    'user_id' => $recipientUser->id,
                    'received_invoice_id' => $invoice->id,
                    'business_profile_id' => null,
                    'vendor_id' => null,
                    'expense_date' => $invoice->issue_date,
                    'due_date' => $invoice->due_date,
                    'category' => 'services',
                    'description' => "Invoice #{$invoice->invoice_number} from " . ($invoice->businessProfile->business_name ?? $invoice->user->name),
                    'reference_number' => $invoice->invoice_number,
                    'payment_method' => null,
                    'status' => $expenseStatus,
                    'currency' => $invoice->currency,
                    'amount' => $invoice->sub_total ?? $invoice->total_amount,
                    'tax_amount' => $invoice->vat_amount ?? 0,
                    'total_amount' => $invoice->total_amount,
                    'notes' => "Automatically created from received invoice (backfilled)",
                ]);
            }

            $created++;
        }

        $this->info("\nSummary:");
        $this->info("  Created: {$created}");
        $this->info("  Skipped: {$skipped}");

        if ($isDryRun) {
            $this->warn("\nThis was a DRY RUN. Run without --dry-run to actually create the expenses.");
        } else {
            $this->info("\n✓ Backfill complete!");
        }

        return 0;
    }
}
