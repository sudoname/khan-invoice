<?php

namespace App\Jobs\WhatsApp;

use App\Models\Invoice;
use App\Models\WhatsApp\AutomationRule;
use App\Models\WhatsApp\AutomationLog;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppFollowupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $invoiceId;
    public int $ruleId;
    public int $attemptNumber;

    /**
     * Number of times to retry on failure.
     */
    public int $tries = 3;

    /**
     * Timeout in seconds.
     */
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(int $invoiceId, int $ruleId, int $attemptNumber)
    {
        $this->invoiceId = $invoiceId;
        $this->ruleId = $ruleId;
        $this->attemptNumber = $attemptNumber;
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppService $whatsAppService): void
    {
        Log::info('Sending WhatsApp follow-up', [
            'invoice_id' => $this->invoiceId,
            'rule_id' => $this->ruleId,
            'attempt_number' => $this->attemptNumber,
        ]);

        // Load invoice and rule
        $invoice = Invoice::find($this->invoiceId);
        $rule = AutomationRule::find($this->ruleId);

        if (!$invoice || !$rule) {
            Log::warning('Invoice or rule not found', [
                'invoice_id' => $this->invoiceId,
                'rule_id' => $this->ruleId,
            ]);
            return;
        }

        // Check if invoice is still unpaid
        if (in_array($invoice->status, ['paid', 'cancelled'])) {
            AutomationLog::logSkipped(
                $rule->id,
                $invoice->wa_conversation_id,
                'invoice',
                $invoice->id,
                'send_followup',
                ['reason' => 'Invoice no longer unpaid', 'status' => $invoice->status]
            );

            Log::info('Invoice no longer requires follow-up', [
                'invoice_id' => $invoice->id,
                'status' => $invoice->status,
            ]);

            return;
        }

        // Check business hours constraint
        if ($rule->hasBusinessHoursConstraint()) {
            $now = now();
            $businessHours = $rule->constraints['business_hours'] ?? [];
            $startHour = $businessHours['start'] ?? 9;
            $endHour = $businessHours['end'] ?? 18;

            if ($now->hour < $startHour || $now->hour >= $endHour) {
                // Reschedule to next business hour
                $nextBusinessHour = $now->copy()->setTime($startHour, 0);
                if ($now->hour >= $endHour) {
                    $nextBusinessHour->addDay();
                }

                $this->release($nextBusinessHour->diffInSeconds($now));

                AutomationLog::logSkipped(
                    $rule->id,
                    $invoice->wa_conversation_id,
                    'invoice',
                    $invoice->id,
                    'send_followup',
                    ['reason' => 'Outside business hours', 'rescheduled_to' => $nextBusinessHour->toIso8601String()]
                );

                Log::info('Follow-up rescheduled to business hours', [
                    'invoice_id' => $invoice->id,
                    'next_run' => $nextBusinessHour->toIso8601String(),
                ]);

                return;
            }
        }

        // Check if invoice has WhatsApp contact
        if (!$invoice->wa_contact_id) {
            AutomationLog::logSkipped(
                $rule->id,
                $invoice->wa_conversation_id,
                'invoice',
                $invoice->id,
                'send_followup',
                ['reason' => 'No WhatsApp contact associated']
            );

            Log::warning('Invoice has no WhatsApp contact', [
                'invoice_id' => $invoice->id,
            ]);

            return;
        }

        try {
            // Get contact
            $contact = $invoice->waContact;

            if (!$contact) {
                throw new \Exception('WhatsApp contact not found');
            }

            // Generate follow-up message
            $message = $this->generateFollowupMessage($invoice, $rule, $this->attemptNumber);

            // Send message
            $waMessage = $whatsAppService->sendText(
                $invoice->user_id,
                $contact->phone_e164,
                $message,
                $invoice->wa_conversation_id
            );

            if (!$waMessage) {
                throw new \Exception('Failed to send WhatsApp message');
            }

            // Update invoice follow-up tracking
            $invoice->update([
                'whatsapp_last_followup_at' => now(),
                'whatsapp_followup_attempts' => ($invoice->whatsapp_followup_attempts ?? 0) + 1,
            ]);

            // Log success
            AutomationLog::logSuccess(
                $rule->id,
                $invoice->wa_conversation_id,
                'invoice',
                $invoice->id,
                'send_followup',
                [
                    'attempt_number' => $this->attemptNumber,
                    'message_id' => $waMessage->id,
                ]
            );

            Log::info('WhatsApp follow-up sent successfully', [
                'invoice_id' => $invoice->id,
                'attempt_number' => $this->attemptNumber,
                'message_id' => $waMessage->id,
            ]);
        } catch (\Exception $e) {
            // Log failure
            AutomationLog::logFailed(
                $rule->id,
                $invoice->wa_conversation_id,
                'invoice',
                $invoice->id,
                'send_followup',
                [
                    'attempt_number' => $this->attemptNumber,
                    'error' => $e->getMessage(),
                ]
            );

            Log::error('Failed to send WhatsApp follow-up', [
                'invoice_id' => $invoice->id,
                'attempt_number' => $this->attemptNumber,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate follow-up message.
     */
    protected function generateFollowupMessage(Invoice $invoice, AutomationRule $rule, int $attemptNumber): string
    {
        // Build variables for template
        $variables = [
            'invoice_number' => $invoice->invoice_number,
            'amount' => number_format($invoice->amount_due, 2),
            'currency' => $invoice->currency,
            'due_date' => $invoice->due_date->format('M d, Y'),
            'days_overdue' => max(0, $invoice->due_date->diffInDays(now(), false)),
            'customer_name' => $invoice->customer?->name ?? 'Customer',
            'business_name' => $invoice->user?->name ?? 'Business',
            'payment_link' => config('app.url') . '/invoice/' . $invoice->public_id,
            'attempt_number' => $attemptNumber,
        ];

        // Render message template
        return $rule->renderMessage($variables);
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendWhatsAppFollowupJob failed permanently', [
            'invoice_id' => $this->invoiceId,
            'rule_id' => $this->ruleId,
            'attempt_number' => $this->attemptNumber,
            'error' => $exception->getMessage(),
        ]);

        // Log failure in automation log
        try {
            $invoice = Invoice::find($this->invoiceId);
            $rule = AutomationRule::find($this->ruleId);

            if ($invoice && $rule) {
                AutomationLog::logFailed(
                    $rule->id,
                    $invoice->wa_conversation_id,
                    'invoice',
                    $invoice->id,
                    'send_followup',
                    [
                        'attempt_number' => $this->attemptNumber,
                        'error' => $exception->getMessage(),
                        'permanently_failed' => true,
                    ]
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to log permanent failure', [
                'invoice_id' => $this->invoiceId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
