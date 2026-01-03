<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Customer;
use App\Models\FeatureFlag;
use App\Models\User;
use App\Services\Payment\PaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestPaymentOrchestration extends Command
{
    protected $signature = 'payment:test-orchestration {--disable : Disable the feature flag after testing}';
    protected $description = 'Test payment orchestration system locally';

    public function handle()
    {
        $this->info('🚀 Testing Payment Orchestration System');
        $this->newLine();

        // Step 1: Check/Enable Feature Flag
        $this->info('Step 1: Checking feature flag...');
        $flag = FeatureFlag::where('key', 'payment_orchestration')->first();

        if (!$flag) {
            $this->error('❌ Feature flag not found. Run migrations first.');
            return 1;
        }

        $wasEnabled = $flag->enabled;

        if (!$flag->enabled) {
            $this->warn('⚠️  Feature flag is disabled. Enabling for test...');
            $flag->enable();
            $this->info('✅ Feature flag enabled');
        } else {
            $this->info('✅ Feature flag already enabled');
        }

        $this->newLine();

        // Step 2: Get or create test invoice
        $this->info('Step 2: Finding test invoice...');

        $invoice = Invoice::with(['customer', 'user'])
            ->whereNotNull('customer_id')
            ->where('payment_status', '!=', 'completed')
            ->where('amount_due', '>', 0)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$invoice) {
            $this->warn('⚠️  No suitable invoice found. Creating test invoice...');
            $invoice = $this->createTestInvoice();
        }

        $customerEmail = $invoice->customer?->email ?? 'test@example.com';

        $this->info("✅ Using Invoice: {$invoice->invoice_number}");
        $this->info("   Customer: {$customerEmail}");
        $this->info("   Amount: ₦" . number_format($invoice->amount_due ?? $invoice->total_amount, 2));
        $this->newLine();

        // Step 3: Initialize Payment
        $this->info('Step 3: Initializing payment...');

        try {
            $paymentService = app(PaymentService::class);

            $result = $paymentService->initializePayment($invoice, [
                'email' => $customerEmail,
                'callback_url' => url('/api/v1/payments/verify/' . uniqid()),
            ]);

            if ($result['success']) {
                $this->info('✅ Payment initialized successfully!');
                $this->newLine();

                $this->table(
                    ['Field', 'Value'],
                    [
                        ['Payment Attempt ID', $result['payment_attempt_id']],
                        ['Reference', $result['reference']],
                        ['Authorization URL', $result['authorization_url']],
                    ]
                );

                $this->newLine();
                $this->info('🔗 Test Payment URL:');
                $this->line($result['authorization_url']);
                $this->newLine();

                $this->info('📋 Next Steps:');
                $this->line('1. Open the URL above in your browser');
                $this->line('2. Use Paystack test card: 4084 0840 8408 4081');
                $this->line('3. CVV: 408, Expiry: 12/30, PIN: 0000');
                $this->line('4. After payment, verify with:');
                $this->line("   php artisan payment:verify {$result['reference']}");
                $this->newLine();

                // Check database
                $this->info('📊 Database Check:');
                $this->line('Payment Attempts: ' . DB::table('payment_attempts')->count());
                $this->line('Payment Events: ' . DB::table('payment_events')->count());
                $this->line('Invoice Payments: ' . DB::table('invoice_payments')->count());

            } else {
                $this->error('❌ Payment initialization failed: ' . $result['message']);
            }

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            $this->line($e->getTraceAsString());
        }

        // Step 4: Cleanup
        if ($this->option('disable') && !$wasEnabled) {
            $this->newLine();
            $this->info('Disabling feature flag...');
            $flag->disable();
            $this->info('✅ Feature flag disabled');
        }

        $this->newLine();
        $this->info('✨ Test complete!');

        return 0;
    }

    protected function createTestInvoice(): Invoice
    {
        $user = User::first();

        if (!$user) {
            $this->error('No users found. Please create a user first.');
            exit(1);
        }

        $customer = Customer::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'user_id' => $user->id,
                'name' => 'Test Customer',
                'phone' => '+2348012345678',
                'address' => 'Test Address',
            ]
        );

        return Invoice::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-TEST-' . time(),
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'sent',
            'currency' => 'NGN',
            'sub_total' => 10000.00,
            'total_amount' => 10000.00,
            'amount_due' => 10000.00,
            'amount_paid' => 0,
            'payment_status' => 'pending',
            'payment_enabled' => true,
        ]);
    }
}
