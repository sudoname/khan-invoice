<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Payment\MerchantAccount;
use App\Models\Payment\LedgerEntry;
use App\Models\Payment\Payout;
use App\Models\Payment\InvoicePayment;

class DiagnoseUserBalance extends Command
{
    protected $signature = 'payments:diagnose-user-balance {email : User email address}';

    protected $description = 'Diagnose payment balance issues for a specific user';

    public function handle()
    {
        $email = $this->argument('email');

        // Find user
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User not found: {$email}");
            return 1;
        }

        $this->info("User: {$user->name} (ID: {$user->id})");
        $this->info(str_repeat('=', 80));
        $this->newLine();

        // Get merchant account
        $merchantAccount = MerchantAccount::where('user_id', $user->id)
            ->where('is_primary', true)
            ->first();

        if (!$merchantAccount) {
            $this->error("No primary merchant account found");
            return 1;
        }

        $this->info("Merchant Account: {$merchantAccount->bank_name} - {$merchantAccount->account_number}");
        $this->newLine();

        // === LEDGER ENTRIES ===
        $this->info('=== LEDGER ENTRIES ===');
        $ledgerEntries = LedgerEntry::where('user_id', $user->id)
            ->orderBy('entry_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        $calculatedBalance = 0;
        $discrepancies = [];

        foreach ($ledgerEntries as $entry) {
            if ($entry->account_type === 'CREDIT') {
                $calculatedBalance += $entry->amount;
            } else {
                $calculatedBalance -= $entry->amount;
            }

            $diff = abs($entry->balance_after - $calculatedBalance);
            $hasDiscrepancy = $diff > 0.01; // Allow for floating point errors

            $line = sprintf(
                "[%s] %s | %s | ₦%s | Balance After: ₦%s | Calculated: ₦%s%s | %s",
                $entry->entry_date->format('Y-m-d H:i'),
                str_pad($entry->entry_type, 20),
                $entry->account_type,
                number_format($entry->amount, 2),
                number_format($entry->balance_after, 2),
                number_format($calculatedBalance, 2),
                $hasDiscrepancy ? ' ❌ MISMATCH' : '',
                $entry->description
            );

            if ($hasDiscrepancy) {
                $this->error($line);
                $discrepancies[] = [
                    'entry_id' => $entry->id,
                    'recorded' => $entry->balance_after,
                    'calculated' => $calculatedBalance,
                    'difference' => $diff,
                ];
            } else {
                $this->line($line);
            }
        }

        $this->newLine();
        $this->info("Total Ledger Entries: {$ledgerEntries->count()}");
        $this->info("Calculated Balance: ₦" . number_format($calculatedBalance, 2));
        $this->info("Method Balance: ₦" . number_format($merchantAccount->getAvailableBalance(), 2));

        if (count($discrepancies) > 0) {
            $this->newLine();
            $this->error("Found " . count($discrepancies) . " balance discrepancies:");
            foreach ($discrepancies as $disc) {
                $this->error("  Entry #{$disc['entry_id']}: Recorded=₦" . number_format($disc['recorded'], 2) .
                           " vs Calculated=₦" . number_format($disc['calculated'], 2) .
                           " (Diff: ₦" . number_format($disc['difference'], 2) . ")");
            }
        }

        $this->newLine();

        // === INVOICE PAYMENTS ===
        $this->info('=== INVOICE PAYMENTS ===');
        $invoicePayments = InvoicePayment::whereHas('invoice', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->orderBy('created_at', 'desc')->get();

        foreach ($invoicePayments as $payment) {
            $hasLedgerEntry = LedgerEntry::where('invoice_payment_id', $payment->id)
                ->where('entry_type', 'PAYMENT_RECEIVED')
                ->exists();

            $line = sprintf(
                "%s | %s | Gross: ₦%s | Net: ₦%s | Ledger Entry: %s | Created: %s",
                $payment->reference,
                $payment->status,
                number_format($payment->gross_received ?? 0, 2),
                number_format($payment->net_received ?? 0, 2),
                $hasLedgerEntry ? '✓' : '❌ MISSING',
                $payment->created_at->format('Y-m-d H:i')
            );

            if (!$hasLedgerEntry && $payment->status === 'COMPLETED') {
                $this->error($line);
            } else {
                $this->line($line);
            }
        }

        $this->newLine();
        $this->info("Total Invoice Payments: {$invoicePayments->count()}");
        $this->info("Missing Ledger Entries: " . $invoicePayments->filter(function ($p) {
            return $p->status === 'COMPLETED' && !LedgerEntry::where('invoice_payment_id', $p->id)
                ->where('entry_type', 'PAYMENT_RECEIVED')
                ->exists();
        })->count());

        $this->newLine();

        // === PAYOUTS ===
        $this->info('=== PAYOUTS ===');
        $payouts = Payout::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($payouts as $payout) {
            $ledgerEntryCount = LedgerEntry::where('payout_id', $payout->id)->count();

            $line = sprintf(
                "%s | %s | Gross: ₦%s | Fee: ₦%s | Net: ₦%s | Ledger Entries: %d%s | Created: %s",
                $payout->reference,
                str_pad($payout->status, 10),
                number_format($payout->gross_amount, 2),
                number_format($payout->payout_fee, 2),
                number_format($payout->net_amount, 2),
                $ledgerEntryCount,
                $ledgerEntryCount === 0 ? ' ❌ MISSING' : '',
                $payout->created_at->format('Y-m-d H:i')
            );

            if ($ledgerEntryCount === 0) {
                $this->error($line);
            } else {
                $this->line($line);
            }

            // Show ledger entries for this payout
            $payoutLedgers = LedgerEntry::where('payout_id', $payout->id)->get();
            foreach ($payoutLedgers as $ledger) {
                $this->line("  → {$ledger->entry_type} ({$ledger->account_type}): ₦" . number_format($ledger->amount, 2));
            }
        }

        $this->newLine();
        $this->info("Total Payouts: {$payouts->count()}");
        $this->info("Pending: " . $payouts->whereIn('status', ['PENDING', 'PROCESSING'])->count());
        $this->info("Completed: " . $payouts->where('status', 'COMPLETED')->count());
        $this->info("Failed: " . $payouts->where('status', 'FAILED')->count());
        $this->info("Missing Ledger Entries: " . $payouts->filter(function ($p) {
            return LedgerEntry::where('payout_id', $p->id)->count() === 0;
        })->count());

        $this->newLine();

        // === SUMMARY ===
        $this->info('=== SUMMARY ===');
        $this->info("Payout Balance (Ledger): ₦" . number_format($merchantAccount->getAvailableBalance(), 2));
        $this->info("Pending Payouts: ₦" . number_format($merchantAccount->getPendingPayoutAmount(), 2));
        $this->info("Available Balance (After Pending): ₦" . number_format($merchantAccount->getAvailableBalanceAfterPending(), 2));
        $this->info("Completed Payouts: ₦" . number_format($merchantAccount->getCompletedPayoutAmount(), 2));

        $this->newLine();

        // === ISSUES FOUND ===
        $issues = [];

        if (count($discrepancies) > 0) {
            $issues[] = "Balance calculation discrepancies found in ledger entries";
        }

        $missingPaymentLedgers = $invoicePayments->filter(function ($p) {
            return $p->status === 'COMPLETED' && !LedgerEntry::where('invoice_payment_id', $p->id)
                ->where('entry_type', 'PAYMENT_RECEIVED')
                ->exists();
        })->count();

        if ($missingPaymentLedgers > 0) {
            $issues[] = "{$missingPaymentLedgers} completed invoice payments missing ledger entries";
        }

        $missingPayoutLedgers = $payouts->filter(function ($p) {
            return LedgerEntry::where('payout_id', $p->id)->count() === 0;
        })->count();

        if ($missingPayoutLedgers > 0) {
            $issues[] = "{$missingPayoutLedgers} payouts missing ledger entries";
        }

        if (count($issues) > 0) {
            $this->newLine();
            $this->error('=== ISSUES FOUND ===');
            foreach ($issues as $issue) {
                $this->error("  ❌ {$issue}");
            }
            $this->newLine();
            $this->info('Recommended Actions:');
            if ($missingPaymentLedgers > 0) {
                $this->info("  1. Run: php artisan payments:backfill-ledger --user={$user->id}");
            }
            if ($missingPayoutLedgers > 0) {
                $this->warn("  2. Contact developer: Payouts without ledger entries need investigation");
            }
            if (count($discrepancies) > 0) {
                $this->info("  3. Run: php artisan payments:recalculate-balances --user={$user->id}");
            }
        } else {
            $this->info('✓ No issues found - all balances are correct');
        }

        return 0;
    }
}
