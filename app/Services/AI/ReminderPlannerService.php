<?php

namespace App\Services\AI;

use App\Models\Invoice;
use App\Models\PaymentReminder;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ReminderPlannerService
{
    /**
     * Generate reminder schedule for an invoice
     *
     * @param Invoice $invoice
     * @return Collection
     */
    public function plan(Invoice $invoice): Collection
    {
        if (!$invoice->due_date) {
            Log::warning('Cannot plan reminders for invoice without due date', [
                'invoice_id' => $invoice->id,
            ]);
            return collect();
        }

        $config = config('kinvoice.ai.reminders');
        $schedule = $config['schedule'];
        $timezone = $config['business_hours']['timezone'];

        $reminders = collect();

        // Before due date reminders
        foreach ($schedule['before_due'] as $daysBefore) {
            $scheduledDate = $invoice->due_date->copy()->subDays(abs($daysBefore));
            if ($scheduledDate->isFuture()) {
                $reminders->push($this->createReminderData($invoice, $scheduledDate, $timezone, 'before_due', $daysBefore));
            }
        }

        // On due date reminder
        foreach ($schedule['on_due'] as $day) {
            $scheduledDate = $invoice->due_date->copy();
            if ($scheduledDate->isFuture()) {
                $reminders->push($this->createReminderData($invoice, $scheduledDate, $timezone, 'on_due', 0));
            }
        }

        // After due date reminders
        foreach ($schedule['after_due'] as $daysAfter) {
            $scheduledDate = $invoice->due_date->copy()->addDays($daysAfter);
            $reminders->push($this->createReminderData($invoice, $scheduledDate, $timezone, 'overdue', $daysAfter));
        }

        return $reminders;
    }

    /**
     * Persist reminder plan to database
     *
     * @param Invoice $invoice
     * @param string $channel
     * @return Collection
     */
    public function persistPlan(Invoice $invoice, string $channel = 'email'): Collection
    {
        $plan = $this->plan($invoice);

        $persisted = collect();

        foreach ($plan as $reminderData) {
            $reminder = PaymentReminder::create([
                'invoice_id' => $invoice->id,
                'channel' => $channel,
                'scheduled_at' => $reminderData['scheduled_at'],
                'status' => 'pending',
                'message' => $this->generateReminderMessage($invoice, $reminderData['type']),
                'recipient' => $this->getRecipient($invoice, $channel),
                'reference' => PaymentReminder::generateReference(),
            ]);

            $persisted->push($reminder);

            Log::info('Payment reminder scheduled', [
                'invoice_id' => $invoice->id,
                'reminder_id' => $reminder->id,
                'channel' => $channel,
                'scheduled_at' => $reminder->scheduled_at,
                'type' => $reminderData['type'],
            ]);
        }

        return $persisted;
    }

    /**
     * Update existing reminder plan for an invoice
     *
     * @param Invoice $invoice
     * @param string $channel
     * @return Collection
     */
    public function updatePlan(Invoice $invoice, string $channel = 'email'): Collection
    {
        // Cancel existing pending reminders
        PaymentReminder::where('invoice_id', $invoice->id)
            ->where('status', 'pending')
            ->update(['status' => 'canceled']);

        // Create new plan
        return $this->persistPlan($invoice, $channel);
    }

    /**
     * Get reminders due to be sent now
     *
     * @return Collection
     */
    public function getDueReminders(): Collection
    {
        $config = config('kinvoice.ai.reminders');

        $query = PaymentReminder::with('invoice')
            ->pending()
            ->where('scheduled_at', '<=', now());

        // Apply business hours filter
        if ($config['business_hours']) {
            $now = now($config['business_hours']['timezone']);
            $hour = $now->hour;

            if ($hour < $config['business_hours']['start'] || $hour >= $config['business_hours']['end']) {
                // Outside business hours, return empty
                return collect();
            }
        }

        // Apply weekend filter
        if ($config['skip_weekends']) {
            $now = now($config['business_hours']['timezone']);
            if ($now->isWeekend()) {
                return collect();
            }
        }

        return $query->get();
    }

    /**
     * Create reminder data structure
     *
     * @param Invoice $invoice
     * @param Carbon $scheduledDate
     * @param string $timezone
     * @param string $type
     * @param int $daysOffset
     * @return array
     */
    protected function createReminderData(Invoice $invoice, Carbon $scheduledDate, string $timezone, string $type, int $daysOffset): array
    {
        $config = config('kinvoice.ai.reminders');

        // Adjust to business hours
        $scheduledAt = $scheduledDate->copy()->setTimezone($timezone);

        // Set to start of business hours
        $businessStart = $config['business_hours']['start'] ?? 9;
        $scheduledAt->setTime($businessStart, 0, 0);

        // Skip weekends if configured
        if ($config['skip_weekends']) {
            while ($scheduledAt->isWeekend()) {
                $scheduledAt->addDay();
            }
        }

        return [
            'invoice_id' => $invoice->id,
            'scheduled_at' => $scheduledAt,
            'type' => $type,
            'days_offset' => $daysOffset,
            'description' => $this->getReminderDescription($type, $daysOffset),
        ];
    }

    /**
     * Get reminder description based on type
     *
     * @param string $type
     * @param int $daysOffset
     * @return string
     */
    protected function getReminderDescription(string $type, int $daysOffset): string
    {
        return match ($type) {
            'before_due' => abs($daysOffset) === 1
                ? 'Reminder: Payment due tomorrow'
                : "Reminder: Payment due in " . abs($daysOffset) . " days",
            'on_due' => 'Reminder: Payment due today',
            'overdue' => $daysOffset === 1
                ? 'Overdue: Payment was due yesterday'
                : "Overdue: Payment was due {$daysOffset} days ago",
            default => 'Payment reminder',
        };
    }

    /**
     * Generate reminder message
     *
     * @param Invoice $invoice
     * @param string $type
     * @return string
     */
    protected function generateReminderMessage(Invoice $invoice, string $type): string
    {
        $customerName = $invoice->customer?->name ?? 'Valued Customer';
        $invoiceNumber = $invoice->invoice_number;
        $amount = '₦' . number_format($invoice->total_amount, 2);
        $dueDate = $invoice->due_date->format('F j, Y');

        $message = match ($type) {
            'before_due' => "Dear {$customerName},\n\nThis is a friendly reminder that payment for Invoice {$invoiceNumber} (Amount: {$amount}) is due on {$dueDate}.\n\nThank you for your prompt attention to this matter.",
            'on_due' => "Dear {$customerName},\n\nThis is a reminder that payment for Invoice {$invoiceNumber} (Amount: {$amount}) is due today, {$dueDate}.\n\nPlease process payment at your earliest convenience.",
            'overdue' => "Dear {$customerName},\n\nWe notice that payment for Invoice {$invoiceNumber} (Amount: {$amount}) was due on {$dueDate} and remains outstanding.\n\nPlease arrange payment as soon as possible. If you have already paid, please disregard this message.",
            default => "Payment reminder for Invoice {$invoiceNumber}",
        };

        return $message;
    }

    /**
     * Get recipient based on channel
     *
     * @param Invoice $invoice
     * @param string $channel
     * @return string|null
     */
    protected function getRecipient(Invoice $invoice, string $channel): ?string
    {
        return match ($channel) {
            'email' => $invoice->customer?->email,
            'whatsapp', 'sms' => $invoice->customer?->phone,
            default => null,
        };
    }

    /**
     * Cancel all reminders for an invoice
     *
     * @param Invoice $invoice
     * @return int
     */
    public function cancelAllReminders(Invoice $invoice): int
    {
        $count = PaymentReminder::where('invoice_id', $invoice->id)
            ->where('status', 'pending')
            ->update(['status' => 'canceled']);

        Log::info('Canceled payment reminders', [
            'invoice_id' => $invoice->id,
            'count' => $count,
        ]);

        return $count;
    }

    /**
     * Get reminder statistics for an invoice
     *
     * @param Invoice $invoice
     * @return array
     */
    public function getStatistics(Invoice $invoice): array
    {
        return [
            'total' => PaymentReminder::forInvoice($invoice->id)->count(),
            'pending' => PaymentReminder::forInvoice($invoice->id)->pending()->count(),
            'sent' => PaymentReminder::forInvoice($invoice->id)->sent()->count(),
            'failed' => PaymentReminder::forInvoice($invoice->id)->failed()->count(),
            'next_scheduled' => PaymentReminder::forInvoice($invoice->id)
                ->pending()
                ->orderBy('scheduled_at')
                ->first()?->scheduled_at,
        ];
    }
}
