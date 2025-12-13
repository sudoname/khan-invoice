<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Notifications\PaymentReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPaymentReminderJob implements ShouldQueue
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
        public int $daysUntilDue
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
                Log::info('Payment reminder skipped - invoice already paid or cancelled', [
                    'invoice_id' => $this->invoice->id,
                    'invoice_number' => $this->invoice->invoice_number,
                    'status' => $this->invoice->status,
                ]);
                return;
            }

            // Check if invoice user has reminder preferences enabled
            $user = $this->invoice->user;
            $preferences = $user->notificationPreferences;

            if (!$preferences) {
                Log::warning('Payment reminder skipped - no preferences found', [
                    'invoice_id' => $this->invoice->id,
                    'user_id' => $user->id,
                ]);
                return;
            }

            // Check if user has any reminder channel enabled
            if (!$preferences->email_payment_reminder && !$preferences->canSendSms('payment_reminder')) {
                Log::info('Payment reminder skipped - user disabled all reminder channels', [
                    'invoice_id' => $this->invoice->id,
                    'user_id' => $user->id,
                ]);
                return;
            }

            // Send reminder notification
            $this->invoice->customer->notify(new PaymentReminderNotification($this->invoice, $this->daysUntilDue));

            Log::info('Payment reminder queued successfully', [
                'invoice_id' => $this->invoice->id,
                'invoice_number' => $this->invoice->invoice_number,
                'customer_id' => $this->invoice->customer->id,
                'days_until_due' => $this->daysUntilDue,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send payment reminder', [
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
        Log::error('Payment reminder job failed after all retries', [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'error' => $exception->getMessage(),
        ]);
    }
}
