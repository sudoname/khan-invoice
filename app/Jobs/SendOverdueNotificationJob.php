<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Notifications\InvoiceOverdueNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOverdueNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Invoice $invoice,
        public int $daysOverdue
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Reload invoice to ensure fresh data
            $this->invoice->load('customer');

            // Check if invoice is still unpaid
            if (in_array($this->invoice->status, ['paid', 'cancelled'])) {
                Log::info('Overdue notification skipped - invoice already paid or cancelled', [
                    'invoice_id' => $this->invoice->id,
                    'invoice_number' => $this->invoice->invoice_number,
                    'status' => $this->invoice->status,
                ]);
                return;
            }

            // Verify invoice is actually overdue
            if (!$this->invoice->due_date->isPast()) {
                Log::warning('Overdue notification skipped - invoice not overdue', [
                    'invoice_id' => $this->invoice->id,
                    'due_date' => $this->invoice->due_date->toDateString(),
                ]);
                return;
            }

            // Check if invoice user has overdue notification preferences enabled
            $user = $this->invoice->user;
            $preferences = $user->notificationPreferences;

            if (!$preferences) {
                Log::warning('Overdue notification skipped - no preferences found', [
                    'invoice_id' => $this->invoice->id,
                    'user_id' => $user->id,
                ]);
                return;
            }

            // Check if user has any overdue notification channel enabled
            if (!$preferences->email_invoice_overdue && !$preferences->canSendSms('invoice_overdue')) {
                Log::info('Overdue notification skipped - user disabled all overdue channels', [
                    'invoice_id' => $this->invoice->id,
                    'user_id' => $user->id,
                ]);
                return;
            }

            // Send overdue notification
            $this->invoice->customer->notify(new InvoiceOverdueNotification($this->invoice, $this->daysOverdue));

            Log::info('Overdue notification queued successfully', [
                'invoice_id' => $this->invoice->id,
                'invoice_number' => $this->invoice->invoice_number,
                'customer_id' => $this->invoice->customer->id,
                'days_overdue' => $this->daysOverdue,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send overdue notification', [
                'invoice_id' => $this->invoice->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Overdue notification job failed after all retries', [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'error' => $exception->getMessage(),
        ]);
    }
}
