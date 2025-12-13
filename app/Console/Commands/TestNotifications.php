<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Payment;
use App\Notifications\InvoiceOverdueNotification;
use App\Notifications\InvoiceSentNotification;
use App\Notifications\PaymentReceivedNotification;
use App\Notifications\PaymentReminderNotification;
use Illuminate\Console\Command;

class TestNotifications extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'notifications:test {invoice_id} {type=all}';

    /**
     * The console command description.
     */
    protected $description = 'Test sending notifications for an invoice. Types: payment_received, invoice_sent, payment_reminder, invoice_overdue, all';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $invoiceId = $this->argument('invoice_id');
        $type = $this->argument('type');

        // Find invoice with relationships
        $invoice = Invoice::with(['customer', 'payments'])
            ->find($invoiceId);

        if (!$invoice) {
            $this->error('Invoice not found with ID: ' . $invoiceId);
            return Command::FAILURE;
        }

        $this->info('Testing notifications for Invoice: ' . $invoice->invoice_number);
        $this->info('Customer: ' . $invoice->customer->name . ' (' . $invoice->customer->email . ')');
        $this->newLine();

        // Check notification preferences
        $user = $invoice->user;
        $preferences = $user->notificationPreferences;

        if (!$preferences) {
            $this->warn('No notification preferences found. Creating default preferences...');
            $preferences = $user->notificationPreferences()->create([
                'sms_enabled' => false,
                'sms_credits_remaining' => 0,
                'email_payment_received' => true,
                'email_invoice_sent' => true,
                'email_payment_reminder' => true,
                'email_invoice_overdue' => true,
            ]);
        }

        $this->info('Notification Preferences:');
        $this->info('  SMS Enabled: ' . ($preferences->sms_enabled ? 'Yes' : 'No'));
        $this->info('  SMS Credits: ' . $preferences->sms_credits_remaining);
        $this->info('  Email Payment Received: ' . ($preferences->email_payment_received ? 'Yes' : 'No'));
        $this->info('  Email Invoice Sent: ' . ($preferences->email_invoice_sent ? 'Yes' : 'No'));
        $this->info('  Email Payment Reminder: ' . ($preferences->email_payment_reminder ? 'Yes' : 'No'));
        $this->info('  Email Invoice Overdue: ' . ($preferences->email_invoice_overdue ? 'Yes' : 'No'));
        $this->newLine();

        try {
            // Test payment received notification
            if ($type === 'payment_received' || $type === 'all') {
                $this->info('Testing: Payment Received Notification');

                // Get or create a test payment
                $payment = $invoice->payments()->first();
                if (!$payment) {
                    $this->warn('  No payment found. Creating test payment...');
                    $payment = Payment::create([
                        'invoice_id' => $invoice->id,
                        'amount' => 1000.00,
                        'payment_date' => now(),
                        'payment_method' => 'test',
                        'reference_number' => 'TEST-' . time(),
                        'notes' => 'Test payment for notification testing',
                    ]);
                }

                $invoice->customer->notify(new PaymentReceivedNotification($payment, $invoice));
                $this->info('  ✅ Payment Received notification queued');
                $this->newLine();
            }

            // Test invoice sent notification
            if ($type === 'invoice_sent' || $type === 'all') {
                $this->info('Testing: Invoice Sent Notification');
                $invoice->customer->notify(new InvoiceSentNotification($invoice));
                $this->info('  ✅ Invoice Sent notification queued');
                $this->newLine();
            }

            // Test payment reminder notification
            if ($type === 'payment_reminder' || $type === 'all') {
                $this->info('Testing: Payment Reminder Notification');
                $daysUntilDue = $invoice->due_date->diffInDays(now(), false);
                $daysUntilDue = $daysUntilDue < 0 ? abs($daysUntilDue) : 0;
                $invoice->customer->notify(new PaymentReminderNotification($invoice, $daysUntilDue));
                $this->info('  ✅ Payment Reminder notification queued (days until due: ' . $daysUntilDue . ')');
                $this->newLine();
            }

            // Test invoice overdue notification
            if ($type === 'invoice_overdue' || $type === 'all') {
                $this->info('Testing: Invoice Overdue Notification');
                $daysOverdue = now()->diffInDays($invoice->due_date, false);
                $daysOverdue = $daysOverdue < 0 ? abs($daysOverdue) : 1;
                $invoice->customer->notify(new InvoiceOverdueNotification($invoice, $daysOverdue));
                $this->info('  ✅ Invoice Overdue notification queued (days overdue: ' . $daysOverdue . ')');
                $this->newLine();
            }

            $this->info('========================================');
            $this->info('Notifications queued successfully!');
            $this->info('========================================');
            $this->newLine();
            $this->info('Next steps:');
            $this->info('1. Process the queue: php artisan queue:work --once');
            $this->info('2. Check sms_logs table for SMS delivery status');
            $this->info('3. Check notifications table for database notifications');
            $this->info('4. Check email logs (if mail driver is configured)');
            $this->newLine();

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Error sending notifications: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
