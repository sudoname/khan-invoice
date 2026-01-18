<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Payment\LedgerEntry;
use App\Models\Payment\MerchantAccount;
use App\Services\PaystackService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillPaymentLedgerEntries extends Command
{
    protected $signature = 'payments:backfill-ledger {--dry-run : Run without making changes} {--user= : Only process specific user ID}';

    protected $description = 'Backfill ledger entries for historical payments that were received before payment orchestration';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $specificUser = $this->option('user');

        $this->info('Starting payment ledger backfill...');
        $this->info($dryRun ? '[DRY RUN MODE - No changes will be made]' : '[LIVE MODE - Changes will be saved]');
        $this->newLine();

        // Find all invoices with payments but no ledger entries
        $query = Invoice::query()
            ->where('amount_paid', '>', 0)
            ->whereNotNull('payment_reference')
            ->with('user');

        if ($specificUser) {
            $query->where('user_id', $specificUser);
        }

        $invoices = $query->get();

        $this->info("Found {$invoices->count()} invoices with payments");
        $this->newLine();

        $processed = 0;
        $skipped = 0;
        $created = 0;
        $errors = 0;

        foreach ($invoices as $invoice) {
            $this->line("Processing Invoice #{$invoice->invoice_number} (User: {$invoice->user->name})");

            // Check if ledger entry already exists for this invoice
            $existingEntry = LedgerEntry::where('invoice_id', $invoice->id)
                ->where('entry_type', 'PAYMENT_RECEIVED')
                ->first();

            if ($existingEntry) {
                $this->warn("  ⊘ Ledger entry already exists - Balance: ₦" . number_format($existingEntry->balance_after, 2));
                $skipped++;
                continue;
            }

            try {
                $paystackService = new PaystackService();

                // Try to verify payment in Paystack
                $result = $paystackService->verifyTransaction($invoice->payment_reference);

                if (!$result['status'] || $result['data']['status'] !== 'success') {
                    $this->warn("  ⊘ Payment not found or not successful in Paystack");
                    $this->warn("    Reference: {$invoice->payment_reference}");
                    $skipped++;
                    continue;
                }

                $data = $result['data'];
                $amountPaid = $data['amount'] / 100; // Convert from kobo
                $fees = ($data['fees'] ?? 0) / 100;
                $netReceived = $amountPaid - $fees;

                $this->info("  ✓ Payment verified in Paystack");
                $this->line("    Amount: ₦" . number_format($amountPaid, 2));
                $this->line("    Fees: ₦" . number_format($fees, 2));
                $this->line("    Net: ₦" . number_format($netReceived, 2));

                // Get or create primary merchant account
                $merchantAccount = MerchantAccount::where('user_id', $invoice->user_id)
                    ->where('is_primary', true)
                    ->first();

                if (!$merchantAccount) {
                    $this->warn("  ⊘ No primary merchant account found - skipping");
                    $skipped++;
                    continue;
                }

                if (!$dryRun) {
                    DB::beginTransaction();

                    try {
                        // Get current balance
                        $currentBalance = $merchantAccount->getAvailableBalance();
                        $balanceAfter = $currentBalance + $netReceived;

                        // Create ledger entry for payment received
                        LedgerEntry::create([
                            'user_id' => $invoice->user_id,
                            'entry_type' => 'PAYMENT_RECEIVED',
                            'account_type' => 'CREDIT',
                            'amount' => $netReceived,
                            'balance_after' => $balanceAfter,
                            'currency' => 'NGN',
                            'invoice_id' => $invoice->id,
                            'description' => "Payment received for invoice {$invoice->invoice_number} (backfilled)",
                            'reference' => LedgerEntry::generateReference('PAYMENT'),
                            'entry_date' => $invoice->paid_at ?? now(),
                        ]);

                        // Create ledger entry for gateway fees if applicable
                        if ($fees > 0) {
                            LedgerEntry::create([
                                'user_id' => $invoice->user_id,
                                'entry_type' => 'GATEWAY_FEE',
                                'account_type' => 'DEBIT',
                                'amount' => $fees,
                                'balance_after' => $balanceAfter, // Already deducted in net_received
                                'currency' => 'NGN',
                                'invoice_id' => $invoice->id,
                                'description' => "Gateway fees for invoice {$invoice->invoice_number} (backfilled)",
                                'reference' => LedgerEntry::generateReference('GATEWAY_FEE'),
                                'entry_date' => $invoice->paid_at ?? now(),
                            ]);
                        }

                        // Update invoice if needed
                        if ($invoice->amount_paid != $amountPaid || $invoice->payment_status !== 'completed') {
                            $invoice->update([
                                'amount_paid' => $amountPaid,
                                'amount_due' => max(0, $invoice->total_amount - $amountPaid),
                                'payment_status' => $amountPaid >= $invoice->total_amount ? 'completed' : 'processing',
                                'paid_at' => $invoice->paid_at ?? $data['paid_at'] ?? now(),
                            ]);
                        }

                        DB::commit();

                        $this->info("  ✓ Ledger entries created - New balance: ₦" . number_format($balanceAfter, 2));
                        $created++;
                    } catch (\Exception $e) {
                        DB::rollBack();
                        throw $e;
                    }
                } else {
                    $currentBalance = $merchantAccount->getAvailableBalance();
                    $balanceAfter = $currentBalance + $netReceived;
                    $this->info("  [DRY RUN] Would create ledger entry - New balance would be: ₦" . number_format($balanceAfter, 2));
                    $created++;
                }

                $processed++;

            } catch (\Exception $e) {
                $this->error("  ✗ Error processing invoice: " . $e->getMessage());
                $errors++;
            }

            $this->newLine();
        }

        // Summary
        $this->newLine();
        $this->info('=== Summary ===');
        $this->info("Total invoices checked: {$invoices->count()}");
        $this->info("Ledger entries created: {$created}");
        $this->info("Skipped (already exists): {$skipped}");
        $this->info("Errors: {$errors}");
        $this->newLine();

        if ($dryRun) {
            $this->warn('This was a DRY RUN. No changes were saved to the database.');
            $this->info('Run without --dry-run to apply changes.');
        } else {
            $this->info('✓ Backfill completed successfully!');
        }

        return 0;
    }
}
