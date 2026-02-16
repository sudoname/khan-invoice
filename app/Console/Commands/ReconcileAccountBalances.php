<?php

namespace App\Console\Commands;

use App\Models\Payment\Payout;
use App\Models\Payment\LedgerEntry;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileAccountBalances extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'payment:reconcile-balances
                            {--user= : Check specific user ID}
                            {--fix : Automatically fix discrepancies}
                            {--verbose : Show detailed output}';

    /**
     * The console command description.
     */
    protected $description = 'Reconcile account balances and detect payouts without ledger entries';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Starting Account Balance Reconciliation...');
        $this->newLine();

        $issues = [];

        // Check 1: Find payouts without ledger entries
        $this->info('📋 Checking for payouts without ledger entries...');
        $invalidPayouts = $this->findPayoutsWithoutLedgerEntries();

        if ($invalidPayouts->isNotEmpty()) {
            $this->error("❌ Found {$invalidPayouts->count()} payouts without ledger entries:");

            $table = [];
            foreach ($invalidPayouts as $payout) {
                $table[] = [
                    'ID' => $payout->id,
                    'Reference' => $payout->reference,
                    'User' => $payout->user->name ?? 'Unknown',
                    'Amount' => '₦' . number_format($payout->gross_amount, 2),
                    'Status' => $payout->status,
                    'Created' => $payout->created_at->format('Y-m-d H:i'),
                ];
            }

            $this->table(['ID', 'Reference', 'User', 'Amount', 'Status', 'Created'], $table);

            $issues[] = [
                'type' => 'payouts_without_ledger',
                'count' => $invalidPayouts->count(),
                'payouts' => $invalidPayouts,
            ];
        } else {
            $this->info('✓ All payouts have ledger entries');
        }

        $this->newLine();

        // Check 2: Validate balance calculations
        $this->info('📊 Validating user balance calculations...');

        $users = $this->option('user')
            ? User::where('id', $this->option('user'))->get()
            : User::whereHas('ledgerEntries')->get();

        $balanceIssues = [];

        foreach ($users as $user) {
            $calculatedBalance = LedgerEntry::where('user_id', $user->id)
                ->sum(DB::raw('CASE WHEN account_type = "CREDIT" THEN amount WHEN account_type = "DEBIT" THEN -amount ELSE 0 END'));

            // Get the last balance_after value
            $lastEntry = LedgerEntry::where('user_id', $user->id)
                ->orderBy('entry_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            $recordedBalance = $lastEntry ? $lastEntry->balance_after : 0;

            if (abs($calculatedBalance - $recordedBalance) > 0.01) { // Allow 1 kobo difference for rounding
                $balanceIssues[] = [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'calculated' => $calculatedBalance,
                    'recorded' => $recordedBalance,
                    'difference' => $calculatedBalance - $recordedBalance,
                ];
            }
        }

        if (!empty($balanceIssues)) {
            $this->error("❌ Found " . count($balanceIssues) . " users with balance discrepancies:");

            $table = [];
            foreach ($balanceIssues as $issue) {
                $table[] = [
                    'User ID' => $issue['user_id'],
                    'Name' => $issue['user_name'],
                    'Calculated' => '₦' . number_format($issue['calculated'], 2),
                    'Recorded' => '₦' . number_format($issue['recorded'], 2),
                    'Difference' => '₦' . number_format($issue['difference'], 2),
                ];
            }

            $this->table(['User ID', 'Name', 'Calculated', 'Recorded', 'Difference'], $table);

            $issues[] = [
                'type' => 'balance_discrepancies',
                'count' => count($balanceIssues),
                'issues' => $balanceIssues,
            ];
        } else {
            $this->info('✓ All user balances are correct');
        }

        $this->newLine();

        // Check 3: Find duplicate ledger entries
        $this->info('🔍 Checking for duplicate ledger entries...');
        $duplicates = $this->findDuplicateLedgerEntries();

        if ($duplicates->isNotEmpty()) {
            $this->error("❌ Found {$duplicates->count()} potential duplicate ledger entries:");

            foreach ($duplicates as $duplicate) {
                $this->warn("  Reference {$duplicate->reference}: {$duplicate->count} entries");
            }

            $issues[] = [
                'type' => 'duplicate_entries',
                'count' => $duplicates->count(),
            ];
        } else {
            $this->info('✓ No duplicate ledger entries found');
        }

        $this->newLine();

        // Summary
        if (empty($issues)) {
            $this->info('✅ All checks passed! No accounting issues found.');
            return 0;
        } else {
            $this->error('⚠️  Reconciliation found ' . count($issues) . ' types of issues.');

            if ($this->option('fix')) {
                $this->newLine();
                $this->warn('🔧 Attempting to fix issues...');

                foreach ($issues as $issue) {
                    if ($issue['type'] === 'payouts_without_ledger' && isset($issue['payouts'])) {
                        $this->fixPayoutsWithoutLedger($issue['payouts']);
                    }
                }
            } else {
                $this->newLine();
                $this->info('💡 Run with --fix flag to attempt automatic fixes.');
            }

            return 1;
        }
    }

    /**
     * Find payouts without ledger entries
     */
    protected function findPayoutsWithoutLedgerEntries()
    {
        return Payout::whereNotIn('id', function ($query) {
            $query->select('payout_id')
                ->from('ledger_entries')
                ->whereNotNull('payout_id')
                ->where('entry_type', 'PAYOUT');
        })->get();
    }

    /**
     * Find duplicate ledger entries
     */
    protected function findDuplicateLedgerEntries()
    {
        return LedgerEntry::select('reference', DB::raw('COUNT(*) as count'))
            ->groupBy('reference')
            ->having('count', '>', 1)
            ->get();
    }

    /**
     * Attempt to fix payouts without ledger entries
     */
    protected function fixPayoutsWithoutLedger($payouts)
    {
        foreach ($payouts as $payout) {
            $this->warn("Processing payout {$payout->reference}...");

            if ($payout->status === 'FAILED') {
                $this->info("  → Payout is FAILED, no action needed");
                continue;
            }

            if (in_array($payout->status, ['PROCESSING', 'COMPLETED'])) {
                $this->error("  → CRITICAL: Payout is {$payout->status} without ledger entry!");
                $this->error("  → Marking as FAILED to prevent double-deduction");

                $payout->update([
                    'status' => 'FAILED',
                    'failure_reason' => 'System error: No ledger entry found. Contact support.',
                ]);

                $this->info("  → Marked as FAILED");
            } else {
                $this->info("  → Payout is PENDING, marking as FAILED");

                $payout->update([
                    'status' => 'FAILED',
                    'failure_reason' => 'Created without proper ledger entries',
                ]);
            }
        }
    }
}
