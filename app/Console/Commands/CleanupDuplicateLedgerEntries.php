<?php

namespace App\Console\Commands;

use App\Models\Payment\LedgerEntry;
use App\Models\Payment\MerchantAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupDuplicateLedgerEntries extends Command
{
    protected $signature = 'payments:cleanup-duplicate-ledgers {--dry-run : Run without making changes}';

    protected $description = 'Remove duplicate ledger entries and recalculate balances';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info('Starting duplicate ledger entry cleanup...');
        $this->info($dryRun ? '[DRY RUN MODE - No changes will be made]' : '[LIVE MODE - Changes will be saved]');
        $this->newLine();

        $duplicatesFound = 0;
        $duplicatesRemoved = 0;
        $balancesFixed = 0;

        // Find duplicate PAYMENT_RECEIVED entries (same invoice_payment_id)
        $duplicates = LedgerEntry::select('invoice_payment_id', DB::raw('COUNT(*) as count'))
            ->where('entry_type', 'PAYMENT_RECEIVED')
            ->whereNotNull('invoice_payment_id')
            ->groupBy('invoice_payment_id')
            ->having('count', '>', 1)
            ->get();

        $this->info("Found {$duplicates->count()} payments with duplicate ledger entries");
        $this->newLine();

        foreach ($duplicates as $duplicate) {
            $duplicatesFound++;

            // Get all entries for this payment
            $entries = LedgerEntry::where('invoice_payment_id', $duplicate->invoice_payment_id)
                ->where('entry_type', 'PAYMENT_RECEIVED')
                ->orderBy('created_at', 'asc')
                ->get();

            $first = $entries->first();
            $duplicateEntries = $entries->skip(1);

            $this->line("Payment ID {$duplicate->invoice_payment_id} has {$entries->count()} entries:");
            $this->line("  Keeping: Entry #{$first->id} (Created: {$first->created_at})");

            foreach ($duplicateEntries as $dupEntry) {
                $this->warn("  Removing: Entry #{$dupEntry->id} (Created: {$dupEntry->created_at})");

                if (!$dryRun) {
                    $dupEntry->delete();
                    $duplicatesRemoved++;
                }
            }
        }

        $this->newLine();
        $this->info("Duplicate entries processed");
        $this->newLine();

        // Now recalculate balances for all users with merchant accounts
        $this->info("Recalculating balances...");
        $merchantAccounts = MerchantAccount::where('is_primary', true)->get();

        foreach ($merchantAccounts as $account) {
            $this->line("Recalculating balance for {$account->user->name}...");

            // Get all ledger entries in chronological order
            $entries = LedgerEntry::where('user_id', $account->user_id)
                ->orderBy('entry_date', 'asc')
                ->orderBy('created_at', 'asc')
                ->get();

            if ($entries->isEmpty()) {
                continue;
            }

            $runningBalance = 0;

            if (!$dryRun) {
                DB::beginTransaction();
            }

            try {
                foreach ($entries as $entry) {
                    // Calculate new balance
                    if ($entry->account_type === 'CREDIT') {
                        $runningBalance += $entry->amount;
                    } else {
                        $runningBalance -= $entry->amount;
                    }

                    // Update if balance is different
                    if ($entry->balance_after != $runningBalance) {
                        $this->warn("  Fixing entry #{$entry->id}: ₦{$entry->balance_after} → ₦{$runningBalance}");

                        if (!$dryRun) {
                            $entry->update(['balance_after' => $runningBalance]);
                        }
                        $balancesFixed++;
                    }
                }

                if (!$dryRun) {
                    DB::commit();
                }

                $this->info("  Final balance: ₦" . number_format($runningBalance, 2));
            } catch (\Exception $e) {
                if (!$dryRun) {
                    DB::rollBack();
                }
                $this->error("  Error: " . $e->getMessage());
            }
        }

        // Summary
        $this->newLine();
        $this->info('=== Summary ===');
        $this->info("Duplicate payment entries found: {$duplicatesFound}");
        $this->info("Duplicate entries removed: {$duplicatesRemoved}");
        $this->info("Balance fields corrected: {$balancesFixed}");
        $this->newLine();

        if ($dryRun) {
            $this->warn('This was a DRY RUN. No changes were saved to the database.');
            $this->info('Run without --dry-run to apply changes.');
        } else {
            $this->info('✓ Cleanup completed successfully!');
        }

        return 0;
    }
}
