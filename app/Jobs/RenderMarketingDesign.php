<?php

namespace App\Jobs;

use App\Models\MarketingDesign;
use App\Services\AI\MarketingDesignService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RenderMarketingDesign implements ShouldQueue
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
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public MarketingDesign $design
    ) {
        // Set queue based on config
        $this->onQueue(config('marketing.queue.queue_name', 'marketing'));
    }

    /**
     * Execute the job.
     */
    public function handle(MarketingDesignService $marketingService): void
    {
        try {
            // Reload design to ensure fresh data
            $this->design->refresh();

            // Check if design is already completed or failed
            if ($this->design->isCompleted()) {
                Log::info('Design rendering skipped - already completed', [
                    'design_id' => $this->design->id,
                    'status' => $this->design->status,
                ]);
                return;
            }

            if ($this->design->hasFailed() && $this->design->render_attempts >= config('marketing.queue.max_attempts', 3)) {
                Log::warning('Design rendering skipped - max attempts exceeded', [
                    'design_id' => $this->design->id,
                    'attempts' => $this->design->render_attempts,
                ]);
                return;
            }

            // Check if design has required data
            if (empty($this->design->design_json)) {
                Log::error('Design rendering failed - missing design_json', [
                    'design_id' => $this->design->id,
                ]);
                $this->design->markAsFailed('Missing design JSON data');
                return;
            }

            Log::info('Starting design rendering', [
                'design_id' => $this->design->id,
                'user_id' => $this->design->user_id,
                'template_id' => $this->design->template_id,
                'attempt' => $this->design->render_attempts + 1,
            ]);

            // Render design
            $marketingService->renderDesign($this->design);

            Log::info('Design rendered successfully', [
                'design_id' => $this->design->id,
                'rendered_url' => $this->design->rendered_url,
                'file_size' => $this->design->file_size,
                'dimensions' => "{$this->design->width}x{$this->design->height}",
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to render marketing design', [
                'design_id' => $this->design->id,
                'user_id' => $this->design->user_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Mark design as failed
            $this->design->markAsFailed($e->getMessage());

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Design rendering job failed after all retries', [
            'design_id' => $this->design->id,
            'user_id' => $this->design->user_id,
            'error' => $exception->getMessage(),
        ]);

        // Ensure design is marked as failed
        if (!$this->design->hasFailed()) {
            $this->design->markAsFailed('Job failed after all retries: ' . $exception->getMessage());
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'marketing',
            'design:' . $this->design->id,
            'user:' . $this->design->user_id,
        ];
    }
}
