<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\InvoiceOverdueNotification;
use App\Notifications\InvoiceSentNotification;
use App\Notifications\PaymentReceivedNotification;
use App\Notifications\PaymentReminderNotification;
use App\Notifications\SubscriptionChangedNotification;
use App\Notifications\WelcomeNotification;
use Illuminate\Console\Command;

class GenerateEmailSamplesCommand extends Command
{
    protected $signature = 'emails:samples {email} {--all} {--invoice-sent} {--payment-received} {--payment-reminder} {--invoice-overdue} {--subscription-changed} {--welcome}';
    protected $description = 'Generate sample emails for all notification types';

    public function handle(): int
    {
        $email = $this->argument('email');
        $all = $this->option('all');

        if (!$all && !$this->option('invoice-sent') && !$this->option('payment-received')
            && !$this->option('payment-reminder') && !$this->option('invoice-overdue')
            && !$this->option('subscription-changed') && !$this->option('welcome')) {
            $all = true; // Default to all if no specific type selected
        }

        $this->info("Generating sample emails to: {$email}");
        $this->newLine();

        // Get or create test user
        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->warn("User not found with email {$email}. Using admin user for samples.");
            $user = User::where('role', 'admin')->first();
            if (!$user) {
                $user = User::first();
            }
        }

        if (!$user) {
            $this->error('No users found in database. Please create a user first.');
            return 1;
        }

        // Get sample data
        $invoice = Invoice::with(['customer', 'user', 'payments'])->first();
        $payment = $invoice?->payments()->first() ?? \App\Models\Payment::first();
        $customer = $invoice?->customer ?? Customer::first();
        $subscription = Subscription::with(['plan'])->first();
        $plan = Plan::where('slug', 'professional')->first() ?? Plan::skip(1)->first();
        $freePlan = Plan::where('slug', 'free')->first() ?? Plan::first();

        $count = 0;

        // 1. Invoice Sent
        if ($all || $this->option('invoice-sent')) {
            $this->info('📧 Sending: Invoice Sent sample...');
            if ($invoice && $customer) {
                $customer->notify(new InvoiceSentNotification($invoice));
                $count++;
            } else {
                $this->warn('  ⚠ Skipped: No invoice/customer data found');
            }
        }

        // 2. Payment Received
        if ($all || $this->option('payment-received')) {
            $this->info('📧 Sending: Payment Received sample...');
            if ($invoice && $payment) {
                $user->notify(new PaymentReceivedNotification($payment, $invoice));
                $count++;
            } else {
                $this->warn('  ⚠ Skipped: No invoice/payment data found');
            }
        }

        // 3. Payment Reminder
        if ($all || $this->option('payment-reminder')) {
            $this->info('📧 Sending: Payment Reminder sample...');
            if ($invoice && $customer) {
                $customer->notify(new PaymentReminderNotification($invoice, 3)); // 3 days until due
                $count++;
            } else {
                $this->warn('  ⚠ Skipped: No invoice/customer data found');
            }
        }

        // 4. Invoice Overdue
        if ($all || $this->option('invoice-overdue')) {
            $this->info('📧 Sending: Invoice Overdue sample...');
            if ($invoice && $customer) {
                $customer->notify(new InvoiceOverdueNotification($invoice, 5)); // 5 days overdue
                $count++;
            } else {
                $this->warn('  ⚠ Skipped: No invoice/customer data found');
            }
        }

        // 5. Subscription Changed
        if ($all || $this->option('subscription-changed')) {
            $this->info('📧 Sending: Subscription Changed sample...');
            if ($subscription && $plan && $freePlan) {
                $user->notify(new SubscriptionChangedNotification(
                    $subscription,
                    $freePlan,
                    $plan,
                    'upgrade',
                    null,
                    15000.00
                ));
                $count++;
            } else {
                $this->warn('  ⚠ Skipped: No subscription/plan data found');
            }
        }

        // 6. Welcome
        if ($all || $this->option('welcome')) {
            $this->info('📧 Sending: Welcome Email sample...');
            $user->notify(new WelcomeNotification($user));
            $count++;
        }

        $this->newLine();
        $this->info("✓ Sent {$count} sample email(s) successfully!");
        $this->line('Check email_logs table:');
        $this->line('  SELECT * FROM email_logs ORDER BY id DESC LIMIT ' . $count . ';');

        return 0;
    }
}
