<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Payment\LedgerEntry;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BackfillPaystackLedger extends Command
{
    protected $signature = 'payments:backfill-paystack-ledger
                            {--payment-id= : Specific payment ID to backfill}
                            {--dry-run : Show what would be done without making changes}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Backfill missing ledger entries for Paystack payments';

    public function handle()
    {
        $this->info('🔍 Scanning for Paystack payments without ledger entries...');
        $this->newLine();

        // Get payments without ledger entries
        $query = Payment::where('payment_method', 'paystack')
            ->whereDoesntHave('invoice', function ($q) {
                $q->whereHas('ledgerEntries', function ($q2) {
                    $q2->where('entry_type', 'PAYMENT_RECEIVED');
                });
            })
            ->with(['invoice.user', 'invoice.customer']);

        if ($this->option('payment-id')) {
            $query->where('id', $this->option('payment-id'));
        }

        $paymentsWithoutLedger = $query->get();

        if ($paymentsWithoutLedger->isEmpty()) {
            $this->info('✅ No payments found without ledger entries!');
            return 0;
        }

        $this->warn("Found {$paymentsWithoutLedger->count()} payments without ledger entries:");
        $this->newLine();

        // Display table of payments
        $tableData = [];
        foreach ($paymentsWithoutLedger as $payment) {
            $tableData[] = [
                'ID' => $payment->id,
                'Invoice' => $payment->invoice->invoice_number,
                'Merchant' => $payment->invoice->user->name,
                'Amount' => '₦' . number_format($payment->amount, 2),
                'Date' => $payment->payment_date->format('Y-m-d'),
                'Reference' => $payment->reference_number,
            ];
        }

        $this->table(
            ['ID', 'Invoice', 'Merchant', 'Amount', 'Date', 'Reference'],
            $tableData
        );

        $totalAmount = $paymentsWithoutLedger->sum('amount');
        $this->info("Total amount: ₦" . number_format($totalAmount, 2));
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->showWhatWouldBeDone($paymentsWithoutLedger);
            return 0;
        }

        if (!$this->option('force')) {
            if (!$this->confirm('Do you want to backfill ledger entries for these payments?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->newLine();
        $this->info('🔧 Backfilling ledger entries...');
        $this->newLine();

        $successCount = 0;
        $errorCount = 0;

        foreach ($paymentsWithoutLedger as $payment) {
            try {
                $this->backfillPayment($payment);
                $successCount++;
                $this->info("✓ Backfilled payment #{$payment->id} ({$payment->invoice->invoice_number})");
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("✗ Failed to backfill payment #{$payment->id}: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("✅ Backfill complete!");
        $this->info("   Successful: {$successCount}");
        if ($errorCount > 0) {
            $this->error("   Failed: {$errorCount}");
        }

        return 0;
    }

    protected function showWhatWouldBeDone($payments)
    {
        $this->newLine();
        $this->info('The following ledger entries would be created:');
        $this->newLine();

        foreach ($payments as $payment) {
            $fees = $this->calculateFees($payment->amount);
            $netAmount = $payment->amount - $fees['gateway_fee'] - $fees['platform_fee'];

            $this->line("Payment #{$payment->id} ({$payment->invoice->invoice_number}):");
            $this->line("  → PAYMENT_RECEIVED (CREDIT): ₦" . number_format($netAmount, 2));
            $this->line("  → GATEWAY_FEE (DEBIT): ₦" . number_format($fees['gateway_fee'], 2));
            $this->line("  → PLATFORM_FEE (DEBIT): ₦" . number_format($fees['platform_fee'], 2));
            $this->line("  → Net to merchant: ₦" . number_format($netAmount, 2));
            $this->newLine();
        }
    }

    protected function backfillPayment(Payment $payment)
    {
        DB::transaction(function () use ($payment) {
            $invoice = $payment->invoice;
            $user = $invoice->user;

            // Calculate fees (Paystack: 1.5% + ₦100 capped at ₦2000)
            $fees = $this->calculateFees($payment->amount);
            $netAmount = $payment->amount - $fees['gateway_fee'] - $fees['platform_fee'];

            // Get the last balance for this user
            $lastBalance = LedgerEntry::where('user_id', $user->id)
                ->orderBy('entry_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->value('balance_after') ?? 0;

            // Create PAYMENT_RECEIVED entry (CREDIT)
            $newBalance = $lastBalance + $netAmount;
            LedgerEntry::create([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'invoice_id' => $invoice->id,
                'entry_type' => 'PAYMENT_RECEIVED',
                'account_type' => 'CREDIT',
                'amount' => $netAmount,
                'balance_after' => $newBalance,
                'currency' => 'NGN',
                'description' => "Payment received for invoice {$invoice->invoice_number} (backfilled)",
                'reference' => $payment->reference_number,
                'entry_date' => $payment->payment_date,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create GATEWAY_FEE entry (DEBIT) - doesn't change balance as it's deducted before credit
            LedgerEntry::create([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'invoice_id' => $invoice->id,
                'entry_type' => 'GATEWAY_FEE',
                'account_type' => 'DEBIT',
                'amount' => $fees['gateway_fee'],
                'balance_after' => $newBalance,
                'currency' => 'NGN',
                'description' => "Gateway fees for invoice {$invoice->invoice_number}",
                'reference' => $payment->reference_number . '_gateway_fee',
                'entry_date' => $payment->payment_date,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create PLATFORM_FEE entry (DEBIT) - doesn't change balance as it's deducted before credit
            LedgerEntry::create([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'invoice_id' => $invoice->id,
                'entry_type' => 'PLATFORM_FEE',
                'account_type' => 'DEBIT',
                'amount' => $fees['platform_fee'],
                'balance_after' => $newBalance,
                'currency' => 'NGN',
                'description' => "Platform service charge for invoice {$invoice->invoice_number}",
                'reference' => $payment->reference_number . '_platform_fee',
                'entry_date' => $payment->payment_date,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->line("  Created 3 ledger entries. New balance: ₦" . number_format($newBalance, 2));
        });
    }

    protected function calculateFees(float $amount): array
    {
        // Paystack Nigeria: 1.5% + ₦100 (capped at ₦2,000)
        $gatewayFee = min(($amount * 0.015) + 100, 2000);

        // Platform fee: 2% of gross amount
        $platformFee = $amount * 0.02;

        return [
            'gateway_fee' => round($gatewayFee, 2),
            'platform_fee' => round($platformFee, 2),
        ];
    }
}
