<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Payment\LedgerEntry;
use Illuminate\Support\Facades\DB;

class RecalculateLedgerBalances extends Command
{
    protected $signature = 'payments:recalculate-balances {--user= : Specific user ID to recalculate} {--dry-run : Show what would be changed without making changes}';

    protected $description = 'Recalculate all balance_after fields in ledger entries';

    public function handle()
    {
        $userId = $this->option('user');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User not found: {$userId}");
                return 1;
            }
            $this->info("Recalculating balances for: {$user->name} (ID: {$user->id})");
            $this->recalculateUserBalances($user, $dryRun);
        } else {
            $this->info("Recalculating balances for ALL users...");
            $users = User::whereHas('ledgerEntries')->get();

            $this->withProgressBar($users, function ($user) use ($dryRun) {
                $this->recalculateUserBalances($user, $dryRun);
            });

            $this->newLine(2);
            $this->info("Completed recalculation for {$users->count()} users");
        }

        return 0;
    }

    protected function recalculateUserBalances(User $user, bool $dryRun): void
    {
        // Get all ledger entries in chronological order
        $entries = LedgerEntry::where('user_id', $user->id)
            ->orderBy('entry_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        if ($entries->isEmpty()) {
            return;
        }

        $runningBalance = 0;
        $changedCount = 0;

        foreach ($entries as $entry) {
            // Calculate what the balance should be
            if ($entry->account_type === 'CREDIT') {
                $runningBalance += $entry->amount;
            } else {
                $runningBalance -= $entry->amount;
            }

            // Check if it's different from what's recorded
            $diff = abs($entry->balance_after - $runningBalance);

            if ($diff > 0.01) { // Allow for floating point errors
                $this->line(sprintf(
                    "  Entry #%d (%s): ₦%s → ₦%s (diff: ₦%s)",
                    $entry->id,
                    $entry->entry_type,
                    number_format($entry->balance_after, 2),
                    number_format($runningBalance, 2),
                    number_format($diff, 2)
                ));

                if (!$dryRun) {
                    $entry->update(['balance_after' => $runningBalance]);
                }

                $changedCount++;
            }
        }

        if ($changedCount > 0) {
            $status = $dryRun ? 'Would update' : 'Updated';
            $this->info("  {$status} {$changedCount} entries for {$user->name}");
        }
    }
}
