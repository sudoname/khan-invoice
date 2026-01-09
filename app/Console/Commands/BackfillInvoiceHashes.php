<?php

namespace App\Console\Commands;

use App\Jobs\InvoiceRehashJob;
use App\Models\Invoice;
use App\Models\PublicInvoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillInvoiceHashes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:backfill-hashes
                            {--chunk=500 : Number of records to process per chunk}
                            {--queue=default : Queue to dispatch jobs to}
                            {--type=all : Type of invoices to process (all, private, public)}
                            {--force : Force rehash even if hash already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill document hashes for existing invoices';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $chunk = (int) $this->option('chunk');
        $queue = $this->option('queue');
        $type = $this->option('type');
        $force = $this->option('force');

        $this->info('Starting invoice hash backfill...');
        $this->newLine();

        $totalDispatched = 0;

        // Process private invoices
        if ($type === 'all' || $type === 'private') {
            $totalDispatched += $this->processInvoices($chunk, $queue, $force);
        }

        // Process public invoices
        if ($type === 'all' || $type === 'public') {
            $totalDispatched += $this->processPublicInvoices($chunk, $queue, $force);
        }

        $this->newLine();
        $this->info("Backfill complete! Dispatched {$totalDispatched} rehash jobs to the '{$queue}' queue.");

        return Command::SUCCESS;
    }

    /**
     * Process private invoices
     */
    protected function processInvoices(int $chunk, string $queue, bool $force): int
    {
        $this->line('Processing private invoices...');

        $query = Invoice::query();

        if (!$force) {
            $query->whereNull('document_hash');
        }

        $totalCount = $query->count();

        if ($totalCount === 0) {
            $this->info('No private invoices to process.');
            return 0;
        }

        $this->info("Found {$totalCount} private invoices to process.");

        $bar = $this->output->createProgressBar($totalCount);
        $bar->start();

        $dispatched = 0;

        $query->chunkById($chunk, function ($invoices) use ($queue, $bar, &$dispatched) {
            foreach ($invoices as $invoice) {
                InvoiceRehashJob::dispatch($invoice)->onQueue($queue);
                $dispatched++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Dispatched {$dispatched} private invoice rehash jobs.");

        return $dispatched;
    }

    /**
     * Process public invoices
     */
    protected function processPublicInvoices(int $chunk, string $queue, bool $force): int
    {
        $this->line('Processing public invoices...');

        $query = PublicInvoice::query();

        if (!$force) {
            $query->whereNull('document_hash');
        }

        $totalCount = $query->count();

        if ($totalCount === 0) {
            $this->info('No public invoices to process.');
            return 0;
        }

        $this->info("Found {$totalCount} public invoices to process.");

        $bar = $this->output->createProgressBar($totalCount);
        $bar->start();

        $dispatched = 0;

        $query->chunkById($chunk, function ($invoices) use ($queue, $bar, &$dispatched) {
            foreach ($invoices as $invoice) {
                InvoiceRehashJob::dispatch($invoice)->onQueue($queue);
                $dispatched++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Dispatched {$dispatched} public invoice rehash jobs.");

        return $dispatched;
    }
}
