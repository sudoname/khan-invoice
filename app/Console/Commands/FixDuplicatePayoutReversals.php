<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment\LedgerEntry;
use App\Models\Payment\Payout;
use Illuminate\Support\Facades\DB;

class FixDuplicatePayoutReversals extends Command
{
    protected $signature = 'payments:fix-duplicate-reversals {--dry-run : Show what would be changed without making changes}';

    protected $description = 'Fix duplicate ADJUSTMENT (reversal) ledger entries for the same payout';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Find payouts with multiple ADJUSTMENT reversals
        $duplicates = DB::table('ledger_entries')
            ->select('payout_id', DB::raw('COUNT(*) as count'))
            ->where('entry_type', 'ADJUSTMENT')
            ->where('account_type', 'CREDIT')
            ->whereNotNull('payout_id')
            ->groupBy('payout_id')
            ->having('count', '>', 1)
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('No duplicate payout reversals found');
            return 0;
        }

        $this->info("Found {$duplicates->count()} payouts with duplicate reversals:");
        $this->newLine();

        $totalDeleted = 0;

        foreach ($duplicates as $duplicate) {
            $payout = Payout::find($duplicate->payout_id);

            if (!$payout) {
                $this->warn("  Payout ID {$duplicate->payout_id} not found, skipping");
                continue;
            }

            $this->info("Payout: {$payout->reference} (ID: {$payout->id})");
            $this->line("  Status: {$payout->status}");
            $this->line("  Amount: ₦" . number_format($payout->gross_amount, 2));
            $this->line("  Reversal entries found: {$duplicate->count}");

            // Get all reversal entries for this payout
            $reversals = LedgerEntry::where('payout_id', $payout->id)
                ->where('entry_type', 'ADJUSTMENT')
                ->where('account_type', 'CREDIT')
                ->orderBy('created_at', 'asc')
                ->get();

            // Keep the first one, delete the rest
            $firstReversal = $reversals->first();
            $duplicatesToDelete = $reversals->skip(1);

            $this->line("  Keeping reversal ID {$firstReversal->id} (created: {$firstReversal->created_at})");

            foreach ($duplicatesToDelete as $dup) {
                $this->warn("  → Deleting duplicate reversal ID {$dup->id} (created: {$dup->created_at})");

                if (!$dryRun) {
                    $dup->delete();
                    $totalDeleted++;
                }
            }

            $this->newLine();
        }

        if ($dryRun) {
            $this->info("Would delete {$totalDeleted} duplicate reversal entries");
        } else {
            $this->info("Deleted {$totalDeleted} duplicate reversal entries");
            $this->newLine();
            $this->info("Next steps:");
            $this->info("  1. Run: php artisan payments:recalculate-balances");
            $this->info("  2. Check affected users' payout balances");
        }

        return 0;
    }
}
