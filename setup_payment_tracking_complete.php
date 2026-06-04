<?php

/**
 * Complete Payment Tracking Setup Script
 *
 * This script performs the following:
 * 1. Runs migration to create platform_transactions table
 * 2. Backfills existing paid invoices into tracking system
 * 3. Updates existing Paystack subaccounts with correct percentage
 * 4. Displays comprehensive platform statistics
 *
 * Run: php setup_payment_tracking_complete.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
use App\Models\PaymentSetting;

echo "╔═══════════════════════════════════════════════════════════════════╗\n";
echo "║      KInvoice Payment Tracking & Settlement Fix - Setup Script    ║\n";
echo "╚═══════════════════════════════════════════════════════════════════╝\n\n";

// Step 1: Run migration
echo "Step 1: Running migrations...\n";
try {
    Artisan::call('migrate', ['--force' => true]);
    echo "  ✓ Migrations completed successfully\n\n";
} catch (\Exception $e) {
    echo "  ⚠ Migration error (may already exist): " . $e->getMessage() . "\n\n";
}

// Step 2: Check platform commission setting
echo "Step 2: Checking payment settings...\n";
$platformCommission = (float) PaymentSetting::get('service_charge_percentage', 2);
echo "  Platform Commission: {$platformCommission}%\n";
echo "  Merchant Receives: " . (100 - $platformCommission) . "%\n\n";

// Step 3: Backfill existing paid invoices
echo "Step 3: Backfilling existing paid invoices into platform_transactions...\n";

$paidInvoices = DB::table('public_invoices')
    ->where('payment_status', 'paid')
    ->whereNotNull('paid_at')
    ->select('*')
    ->get();

echo "  Found " . $paidInvoices->count() . " paid invoices\n";

$backfilled = 0;
$skipped = 0;
$totalRevenue = 0;
$totalVolume = 0;

foreach ($paidInvoices as $invoice) {
    // Check if already tracked
    $exists = DB::table('platform_transactions')
        ->where('invoice_id', $invoice->id)
        ->exists();

    if ($exists) {
        $skipped++;
        continue;
    }

    // Calculate amounts
    $totalAmount = (float) $invoice->amount_paid;
    $platformCommissionAmount = $totalAmount * ($platformCommission / 100);
    $merchantAmount = $totalAmount - $platformCommissionAmount;

    // Determine if it was auto-settled
    $hasSubaccount = !empty($invoice->paystack_subaccount_code);
    $settledToMerchant = $hasSubaccount;

    try {
        DB::table('platform_transactions')->insert([
            'invoice_id' => $invoice->id,
            'reference' => 'TXN_BACKFILL_' . $invoice->id . '_' . time() . '_' . rand(1000, 9999),
            'paystack_reference' => $invoice->payment_reference,
            'type' => 'payment',
            'status' => 'success',
            'total_amount' => $totalAmount,
            'platform_commission' => $platformCommissionAmount,
            'merchant_amount' => $merchantAmount,
            'merchant_name' => $invoice->from_name,
            'merchant_email' => $invoice->from_email,
            'merchant_account' => $invoice->from_account_number,
            'merchant_bank' => $invoice->from_bank_name,
            'paystack_subaccount' => $invoice->paystack_subaccount_code,
            'settled_to_merchant' => $settledToMerchant,
            'settled_at' => $settledToMerchant ? $invoice->paid_at : null,
            'customer_name' => $invoice->to_name,
            'customer_email' => $invoice->to_email,
            'metadata' => json_encode([
                'backfilled' => true,
                'original_paid_at' => $invoice->paid_at,
                'invoice_number' => $invoice->invoice_number,
            ]),
            'notes' => $hasSubaccount ? 'Auto-settled via subaccount' : 'Manual settlement required',
            'created_at' => $invoice->paid_at,
            'updated_at' => now(),
        ]);

        $backfilled++;
        $totalRevenue += $platformCommissionAmount;
        $totalVolume += $totalAmount;

        if ($backfilled % 50 === 0) {
            echo "  Processed {$backfilled} invoices...\n";
        }
    } catch (\Exception $e) {
        echo "  ⚠ Error processing invoice {$invoice->id}: " . $e->getMessage() . "\n";
    }
}

echo "\n╔═══════════════════════════════════════════════════════════════════╗\n";
echo "║                     Backfill Summary                               ║\n";
echo "╠═══════════════════════════════════════════════════════════════════╣\n";
echo "║  Total Paid Invoices: " . str_pad($paidInvoices->count(), 44) . "║\n";
echo "║  Newly Backfilled: " . str_pad($backfilled, 47) . "║\n";
echo "║  Skipped (already tracked): " . str_pad($skipped, 38) . "║\n";
echo "║  Total Platform Revenue: ₦" . str_pad(number_format($totalRevenue, 2), 38) . "║\n";
echo "║  Total Transaction Volume: ₦" . str_pad(number_format($totalVolume, 2), 36) . "║\n";
echo "╚═══════════════════════════════════════════════════════════════════╝\n\n";

// Step 4: Update existing Paystack subaccounts
echo "Step 4: Updating existing Paystack subaccounts with correct percentage...\n";

$secretKey = config('services.paystack.secret_key');

if (!$secretKey) {
    echo "  ⚠ Paystack secret key not configured. Skipping subaccount updates.\n\n";
} else {
    $invoicesWithSubaccounts = DB::table('public_invoices')
        ->whereNotNull('paystack_subaccount_code')
        ->select('id', 'public_id', 'from_name', 'paystack_subaccount_code', 'from_bank_name', 'from_account_number')
        ->get();

    echo "  Found " . $invoicesWithSubaccounts->count() . " invoices with subaccounts\n";

    $updated = 0;
    $failed = 0;

    foreach ($invoicesWithSubaccounts as $invoice) {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
                'Content-Type' => 'application/json',
            ])->put('https://api.paystack.co/subaccount/' . $invoice->paystack_subaccount_code, [
                'percentage_charge' => $platformCommission,
            ]);

            if ($response->successful() && $response->json('status')) {
                $updated++;
                if ($updated % 100 === 0) {
                    echo "  Updated {$updated} subaccounts...\n";
                }
            } else {
                $failed++;
            }
        } catch (\Exception $e) {
            $failed++;
        }
    }

    echo "\n  ✓ Updated: {$updated} subaccounts\n";
    if ($failed > 0) {
        echo "  ⚠ Failed: {$failed} subaccounts\n";
    }
    echo "\n";
}

// Step 5: Display comprehensive statistics
echo "Step 5: Calculating platform statistics...\n\n";

$stats = DB::table('platform_transactions')
    ->where('status', 'success')
    ->selectRaw('
        COUNT(*) as total_transactions,
        SUM(total_amount) as total_volume,
        SUM(platform_commission) as total_revenue,
        SUM(merchant_amount) as total_merchant_amount,
        SUM(CASE WHEN settled_to_merchant = 0 THEN 1 ELSE 0 END) as unsettled_count,
        SUM(CASE WHEN settled_to_merchant = 0 THEN merchant_amount ELSE 0 END) as unsettled_amount
    ')
    ->first();

echo "╔═══════════════════════════════════════════════════════════════════╗\n";
echo "║                   Platform Statistics (All Time)                   ║\n";
echo "╠═══════════════════════════════════════════════════════════════════╣\n";
echo "║  Total Transactions: " . str_pad(number_format($stats->total_transactions), 44) . "║\n";
echo "║  Total Transaction Volume: ₦" . str_pad(number_format($stats->total_volume, 2), 36) . "║\n";
echo "║  Total Platform Revenue ({$platformCommission}%): ₦" . str_pad(number_format($stats->total_revenue, 2), 30) . "║\n";
echo "║  Total to Merchants (" . (100 - $platformCommission) . "%): ₦" . str_pad(number_format($stats->total_merchant_amount, 2), 30) . "║\n";
echo "╠═══════════════════════════════════════════════════════════════════╣\n";
echo "║  Unsettled Payments (Need Manual Transfer):                       ║\n";
echo "║    Count: " . str_pad(number_format($stats->unsettled_count), 57) . "║\n";
echo "║    Amount: ₦" . str_pad(number_format($stats->unsettled_amount, 2), 53) . "║\n";
echo "╚═══════════════════════════════════════════════════════════════════╝\n\n";

// Step 6: Top 10 merchants
$topMerchants = DB::table('platform_transactions')
    ->where('status', 'success')
    ->select('merchant_name')
    ->selectRaw('COUNT(*) as transaction_count')
    ->selectRaw('SUM(total_amount) as total_volume')
    ->selectRaw('SUM(platform_commission) as platform_revenue')
    ->groupBy('merchant_name')
    ->orderByDesc('total_volume')
    ->limit(10)
    ->get();

echo "╔═══════════════════════════════════════════════════════════════════╗\n";
echo "║                    Top 10 Merchants by Volume                      ║\n";
echo "╠═══════════════════════════════════════════════════════════════════╣\n";

foreach ($topMerchants as $index => $merchant) {
    $num = str_pad($index + 1, 2);
    $name = strlen($merchant->merchant_name) > 25 ? substr($merchant->merchant_name, 0, 22) . '...' : $merchant->merchant_name;
    $name = str_pad($name, 25);
    $volume = str_pad('₦' . number_format($merchant->total_volume, 0), 15);
    $count = str_pad('(' . $merchant->transaction_count . ' txns)', 12);
    echo "║  {$num}. {$name} {$volume} {$count}  ║\n";
}

echo "╚═══════════════════════════════════════════════════════════════════╝\n\n";

// Final instructions
echo "╔═══════════════════════════════════════════════════════════════════╗\n";
echo "║                        ✅ Setup Complete!                          ║\n";
echo "╠═══════════════════════════════════════════════════════════════════╣\n";
echo "║  Next Steps:                                                       ║\n";
echo "║                                                                     ║\n";
echo "║  1. Visit: https://kinvoice.ng/admin/payments                     ║\n";
echo "║     View your payment dashboard with all transactions              ║\n";
echo "║                                                                     ║\n";
echo "║  2. Check unsettled payments:                                      ║\n";
echo "║     https://kinvoice.ng/admin/payments/unsettled                   ║\n";
echo "║                                                                     ║\n";
echo "║  3. Future payments will:                                          ║\n";
echo "║     • Auto-settle {$platformCommission}% to platform                                ║\n";
echo "║     • Auto-settle " . (100 - $platformCommission) . "% to merchant's bank account                   ║\n";
echo "║     • Be tracked automatically in platform_transactions            ║\n";
echo "║                                                                     ║\n";
echo "║  4. Manually settle unsettled payments:                            ║\n";
echo "║     Use Paystack dashboard to transfer ₦" . number_format($stats->unsettled_amount, 2) . "        ║\n";
echo "║     to the respective merchant bank accounts                       ║\n";
echo "╚═══════════════════════════════════════════════════════════════════╝\n";
