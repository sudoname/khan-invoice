<?php

namespace App\Console\Commands;

use App\Jobs\SendPaymentReminderJob;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendPaymentReminders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'reminders:send-payment';

    /**
     * The console command description.
     */
    protected $description = 'Send payment reminders for invoices due in 3 days or due today';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting payment reminder process...');
        $this->newLine();

        $today = now()->startOfDay();
        $threeDaysFromNow = now()->addDays(3)->endOfDay();
        $dueTodayStart = now()->startOfDay();
        $dueTodayEnd = now()->endOfDay();

        $totalQueued = 0;

        // Find invoices due in 3 days
        $this->info('Checking for invoices due in 3 days...');
        $invoicesDueIn3Days = Invoice::whereDate('due_date', $threeDaysFromNow->toDateString())
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->with(['customer', 'user.notificationPreferences'])
            ->get();

        $this->info("Found {$invoicesDueIn3Days->count()} invoices due in 3 days");

        foreach ($invoicesDueIn3Days as $invoice) {
            try {
                // Check if user has reminders enabled
                $preferences = $invoice->user->notificationPreferences;
                if (!$preferences || (!$preferences->email_payment_reminder && !$preferences->canSendSms('payment_reminder'))) {
                    $this->warn("  Skipping invoice {$invoice->invoice_number} - reminders disabled");
                    continue;
                }

                SendPaymentReminderJob::dispatch($invoice, 3);
                $this->info("  ✅ Queued reminder for invoice {$invoice->invoice_number} (Customer: {$invoice->customer->name})");
                $totalQueued++;

            } catch (\Exception $e) {
                $this->error("  ❌ Failed to queue reminder for invoice {$invoice->invoice_number}: " . $e->getMessage());
                Log::error('Failed to queue payment reminder', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();

        // Find invoices due today
        $this->info('Checking for invoices due today...');
        $invoicesDueToday = Invoice::whereDate('due_date', $today->toDateString())
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->with(['customer', 'user.notificationPreferences'])
            ->get();

        $this->info("Found {$invoicesDueToday->count()} invoices due today");

        foreach ($invoicesDueToday as $invoice) {
            try {
                // Check if user has reminders enabled
                $preferences = $invoice->user->notificationPreferences;
                if (!$preferences || (!$preferences->email_payment_reminder && !$preferences->canSendSms('payment_reminder'))) {
                    $this->warn("  Skipping invoice {$invoice->invoice_number} - reminders disabled");
                    continue;
                }

                SendPaymentReminderJob::dispatch($invoice, 0);
                $this->info("  ✅ Queued reminder for invoice {$invoice->invoice_number} (Customer: {$invoice->customer->name})");
                $totalQueued++;

            } catch (\Exception $e) {
                $this->error("  ❌ Failed to queue reminder for invoice {$invoice->invoice_number}: " . $e->getMessage());
                Log::error('Failed to queue payment reminder', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("========================================");
        $this->info("Payment reminder process complete");
        $this->info("========================================");
        $this->info("Total reminders queued: $totalQueued");
        $this->info("Process queue with: php artisan queue:work");
        $this->newLine();

        Log::info('Payment reminder command completed', [
            'invoices_due_in_3_days' => $invoicesDueIn3Days->count(),
            'invoices_due_today' => $invoicesDueToday->count(),
            'total_queued' => $totalQueued,
        ]);

        return Command::SUCCESS;
    }
}
