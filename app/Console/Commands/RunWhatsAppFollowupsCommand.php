<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\WhatsApp\AutomationRule;
use App\Jobs\WhatsApp\SendWhatsAppFollowupJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunWhatsAppFollowupsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:run-followups
                            {--dry-run : Show what would be sent without actually sending}
                            {--user= : Only process follow-ups for specific user ID}
                            {--limit= : Limit number of follow-ups to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process and send automated WhatsApp follow-ups for unpaid invoices';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $userId = $this->option('user');
        $limit = $this->option('limit');

        $this->info('Running WhatsApp follow-ups...');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No messages will be sent');
        }

        // Get active automation rules for unpaid invoice follow-ups
        $rules = AutomationRule::where('type', 'unpaid_invoice_followup')
            ->where('active', true)
            ->get();

        if ($rules->isEmpty()) {
            $this->warn('No active follow-up rules found');
            return Command::SUCCESS;
        }

        $this->info("Found {$rules->count()} active follow-up rule(s)");

        $totalProcessed = 0;
        $totalQueued = 0;

        foreach ($rules as $rule) {
            $this->line('');
            $this->info("Processing rule: {$rule->name} (ID: {$rule->id})");

            // Get schedule attempts
            $scheduleAttempts = $rule->getScheduleAttempts();

            if (empty($scheduleAttempts)) {
                $this->warn("  Skipping - no schedule defined");
                continue;
            }

            $this->line("  Schedule: " . implode(', ', array_map(fn($m) => "{$m} minutes", $scheduleAttempts)));

            // Get unpaid invoices with WhatsApp contacts
            $query = Invoice::query()
                ->whereIn('status', ['sent', 'overdue', 'partially_paid'])
                ->whereNotNull('wa_contact_id')
                ->whereNotNull('wa_conversation_id');

            // Filter by user if specified
            if ($userId) {
                $query->where('user_id', $userId);
            }

            $invoices = $query->get();

            $this->line("  Found {$invoices->count()} unpaid invoice(s) with WhatsApp contacts");

            foreach ($invoices as $invoice) {
                if ($limit && $totalProcessed >= $limit) {
                    $this->warn('Reached processing limit, stopping');
                    break 2;
                }

                $totalProcessed++;

                // Determine which attempt should be sent
                $attemptToSend = $this->determineAttemptToSend($invoice, $scheduleAttempts);

                if ($attemptToSend === null) {
                    continue; // No follow-up needed at this time
                }

                $this->line("    Invoice #{$invoice->invoice_number} - Attempt {$attemptToSend}");

                if ($dryRun) {
                    $this->line("      [DRY RUN] Would queue follow-up message");
                } else {
                    // Queue follow-up job
                    SendWhatsAppFollowupJob::dispatch($invoice->id, $rule->id, $attemptToSend);
                    $totalQueued++;
                    $this->line("      ✓ Follow-up queued");
                }
            }
        }

        $this->line('');
        $this->info("Processing complete!");
        $this->line("Total invoices processed: {$totalProcessed}");
        $this->line("Total follow-ups queued: {$totalQueued}");

        Log::info('WhatsApp follow-ups command completed', [
            'dry_run' => $dryRun,
            'processed' => $totalProcessed,
            'queued' => $totalQueued,
        ]);

        return Command::SUCCESS;
    }

    /**
     * Determine which attempt should be sent for an invoice.
     * Returns the attempt number (1, 2, 3, etc.) or null if no follow-up needed.
     */
    protected function determineAttemptToSend(Invoice $invoice, array $scheduleAttempts): ?int
    {
        // Calculate minutes since invoice was sent (or created if never sent)
        $referenceDate = $invoice->status === 'draft' ? $invoice->created_at : $invoice->updated_at;
        $minutesSinceSent = $referenceDate->diffInMinutes(now());

        // Get current attempt count
        $currentAttempts = $invoice->whatsapp_followup_attempts ?? 0;

        // Find the next attempt that should be sent
        foreach ($scheduleAttempts as $attemptNumber => $scheduledMinutes) {
            $attemptNumber = $attemptNumber + 1; // Convert 0-based to 1-based

            // Skip if this attempt has already been sent
            if ($attemptNumber <= $currentAttempts) {
                continue;
            }

            // Check if it's time for this attempt
            if ($minutesSinceSent >= $scheduledMinutes) {
                // Check if we already sent a follow-up recently (within last hour)
                if ($invoice->whatsapp_last_followup_at) {
                    $minutesSinceLastFollowup = $invoice->whatsapp_last_followup_at->diffInMinutes(now());
                    if ($minutesSinceLastFollowup < 60) {
                        // Too soon, skip
                        return null;
                    }
                }

                return $attemptNumber;
            }
        }

        // No follow-up needed
        return null;
    }
}
