<?php

namespace App\Console\Commands;

use App\Jobs\SendOverdueNotificationJob;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckOverdueInvoices extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'reminders:check-overdue';

    /**
     * The console command description.
     */
    protected $description = 'Check for overdue invoices, update their status, and send notifications';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting overdue invoice check...');
        $this->newLine();

        $today = now()->startOfDay();

        // Find invoices that are past due date and not yet marked as overdue
        $overdueInvoices = Invoice::where('due_date', '<', $today)
            ->whereNotIn('status', ['paid', 'cancelled', 'overdue'])
            ->with(['customer', 'user.notificationPreferences'])
            ->get();

        $this->info("Found {$overdueInvoices->count()} newly overdue invoices");
        $this->newLine();

        $totalUpdated = 0;
        $totalQueued = 0;

        foreach ($overdueInvoices as $invoice) {
            try {
                $daysOverdue = now()->diffInDays($invoice->due_date, false);
                $daysOverdue = abs($daysOverdue);

                // Update invoice status to overdue
                $oldStatus = $invoice->status;
                $invoice->update(['status' => 'overdue']);
                $totalUpdated++;

                $this->info("  📊 Updated invoice {$invoice->invoice_number}");
                $this->info("     Status: {$oldStatus} → overdue");
                $this->info("     Days overdue: {$daysOverdue}");
                $this->info("     Customer: {$invoice->customer->name}");

                // Check if user has overdue notifications enabled
                $preferences = $invoice->user->notificationPreferences;
                if (!$preferences || (!$preferences->email_invoice_overdue && !$preferences->canSendSms('invoice_overdue'))) {
                    $this->warn("     ⚠️  Notification skipped - user disabled overdue notifications");
                    $this->newLine();
                    continue;
                }

                // Queue overdue notification
                SendOverdueNotificationJob::dispatch($invoice, $daysOverdue);
                $totalQueued++;
                $this->info("     ✅ Overdue notification queued");
                $this->newLine();

            } catch (\Exception $e) {
                $this->error("  ❌ Failed to process invoice {$invoice->invoice_number}: " . $e->getMessage());
                Log::error('Failed to process overdue invoice', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
                $this->newLine();
            }
        }

        // Also check for invoices already marked as overdue but need follow-up notifications
        // (e.g., 7 days overdue, 14 days overdue, 30 days overdue)
        $this->info('Checking for follow-up overdue notifications...');

        $followUpDays = [7, 14, 30]; // Send additional reminders at these intervals
        $followUpQueued = 0;

        foreach ($followUpDays as $days) {
            $targetDate = now()->subDays($days)->startOfDay()->toDateString();

            $invoices = Invoice::whereDate('due_date', $targetDate)
                ->where('status', 'overdue')
                ->with(['customer', 'user.notificationPreferences'])
                ->get();

            foreach ($invoices as $invoice) {
                try {
                    $preferences = $invoice->user->notificationPreferences;
                    if (!$preferences || (!$preferences->email_invoice_overdue && !$preferences->canSendSms('invoice_overdue'))) {
                        continue;
                    }

                    SendOverdueNotificationJob::dispatch($invoice, $days);
                    $followUpQueued++;
                    $this->info("  ✅ Follow-up notification queued for invoice {$invoice->invoice_number} ({$days} days overdue)");

                } catch (\Exception $e) {
                    $this->error("  ❌ Failed to queue follow-up for invoice {$invoice->invoice_number}: " . $e->getMessage());
                }
            }
        }

        $this->newLine();
        $this->info("========================================");
        $this->info("Overdue invoice check complete");
        $this->info("========================================");
        $this->info("Invoices updated to overdue: $totalUpdated");
        $this->info("New overdue notifications queued: $totalQueued");
        $this->info("Follow-up notifications queued: $followUpQueued");
        $this->info("Total notifications queued: " . ($totalQueued + $followUpQueued));
        $this->info("Process queue with: php artisan queue:work");
        $this->newLine();

        Log::info('Check overdue invoices command completed', [
            'invoices_updated' => $totalUpdated,
            'notifications_queued' => $totalQueued,
            'follow_ups_queued' => $followUpQueued,
        ]);

        return Command::SUCCESS;
    }
}
