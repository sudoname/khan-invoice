<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\PublicInvoice;
use App\Services\Invoice\InvoiceHashService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class InvoiceRehashJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The invoice model to rehash
     *
     * @var Invoice|PublicInvoice
     */
    protected Model $invoice;

    /**
     * The invoice type (for logging)
     *
     * @var string
     */
    protected string $invoiceType;

    /**
     * The invoice ID (for logging in case model is deleted)
     *
     * @var int
     */
    protected int $invoiceId;

    /**
     * Create a new job instance.
     *
     * @param Invoice|PublicInvoice $invoice
     */
    public function __construct(Model $invoice)
    {
        if (!($invoice instanceof Invoice) && !($invoice instanceof PublicInvoice)) {
            throw new \InvalidArgumentException('Invoice must be instance of Invoice or PublicInvoice');
        }

        $this->invoice = $invoice;
        $this->invoiceType = get_class($invoice);
        $this->invoiceId = $invoice->id;
    }

    /**
     * Execute the job.
     */
    public function handle(InvoiceHashService $hashService): void
    {
        try {
            // Reload the invoice to ensure we have the latest data
            $invoice = $this->invoice->fresh();

            if (!$invoice) {
                Log::warning('Invoice not found during rehash', [
                    'invoice_type' => $this->invoiceType,
                    'invoice_id' => $this->invoiceId,
                ]);
                return;
            }

            // Compute and update the hash
            $hashService->updateHash($invoice);

            Log::info('Invoice hash updated successfully', [
                'invoice_type' => $this->invoiceType,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'document_hash' => $invoice->document_hash,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to rehash invoice', [
                'invoice_type' => $this->invoiceType,
                'invoice_id' => $this->invoiceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to allow queue retry logic
            throw $e;
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'invoice-hash',
            $this->invoiceType,
            "invoice:{$this->invoiceId}",
        ];
    }
}
