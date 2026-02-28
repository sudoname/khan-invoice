<?php

namespace App\Services\AI;

use App\Jobs\RenderMarketingDesign;
use App\Models\BrandKit;
use App\Models\GenerationQueue;
use App\Models\Invoice;
use App\Models\MarketingDesign;
use App\Models\MarketingTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MarketingDesignService
{
    protected ClaudeIntegrationService $claudeService;
    protected RenderingService $renderingService;

    public function __construct(
        ClaudeIntegrationService $claudeService,
        RenderingService $renderingService
    ) {
        $this->claudeService = $claudeService;
        $this->renderingService = $renderingService;
    }

    /**
     * Create a new marketing design from user prompt
     *
     * @param User|null $user (null for demo mode)
     * @param string $prompt
     * @param int $templateId
     * @param int|null $brandKitId
     * @param int|null $invoiceId
     * @param bool $queueRendering
     * @return MarketingDesign
     * @throws \Exception
     */
    public function createDesign(
        ?User $user,
        string $prompt,
        int $templateId,
        ?int $brandKitId = null,
        ?int $invoiceId = null,
        bool $queueRendering = true
    ): MarketingDesign {
        // Validate inputs
        $this->validateCreateDesign($user, $prompt, $templateId, $brandKitId, $invoiceId);

        // Load related models
        $template = MarketingTemplate::findOrFail($templateId);
        $brandKit = $brandKitId ? BrandKit::findOrFail($brandKitId) : null;
        $invoice = $invoiceId ? Invoice::findOrFail($invoiceId) : null;

        // Check rate limits (skip for demo mode)
        if ($user) {
            $this->checkRateLimit($user);
        }

        DB::beginTransaction();

        try {
            // Create design record
            $design = MarketingDesign::create([
                'user_id' => $user?->id, // Nullable for demo mode
                'template_id' => $template->id,
                'brand_kit_id' => $brandKit?->id,
                'invoice_id' => $invoice?->id,
                'title' => $this->generateTitle($prompt, $template),
                'prompt' => $prompt,
                'status' => 'draft',
            ]);

            // Generate design based on rendering engine
            $renderEngine = config('marketing.rendering.engine');

            if ($renderEngine === 'dalle3') {
                // Generate image prompt for DALL-E 3
                $imagePromptData = $this->claudeService->generateImagePrompt(
                    $prompt,
                    $template,
                    $brandKit,
                    $invoice
                );

                $designJson = [
                    'image_prompt' => $imagePromptData['image_prompt'],
                    'context' => $imagePromptData['context'],
                    'rendering_engine' => 'dalle3',
                ];
            } else {
                // Generate design JSON for HTML-based rendering (Playwright/wkhtmltoimage)
                $designJson = $this->claudeService->generateDesign(
                    $prompt,
                    $template,
                    $brandKit,
                    $invoice
                );
                $designJson['rendering_engine'] = $renderEngine;
            }

            // Update design with JSON/prompt
            $design->update([
                'design_json' => $designJson,
            ]);

            // Increment template usage
            $template->incrementUsage();

            if ($queueRendering) {
                // Queue for rendering
                $this->queueForRendering($design);
            }

            DB::commit();

            Log::info('Marketing design created', [
                'design_id' => $design->id,
                'user_id' => $user?->id ?? 'demo',
                'template_id' => $template->id,
            ]);

            return $design->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create marketing design', [
                'error' => $e->getMessage(),
                'user_id' => $user?->id ?? 'demo',
                'template_id' => $templateId,
            ]);
            throw $e;
        }
    }

    /**
     * Create design from invoice (quick share)
     *
     * @param Invoice $invoice
     * @param int $templateId
     * @param string|null $customMessage
     * @return MarketingDesign
     */
    public function createFromInvoice(
        Invoice $invoice,
        int $templateId,
        ?string $customMessage = null
    ): MarketingDesign {
        $user = $invoice->user;

        // Generate auto-prompt based on invoice status
        $prompt = $this->generateInvoicePrompt($invoice, $customMessage);

        // Get user's default brand kit
        $brandKit = BrandKit::where('user_id', $user->id)
            ->where('is_default', true)
            ->first();

        return $this->createDesign(
            user: $user,
            prompt: $prompt,
            templateId: $templateId,
            brandKitId: $brandKit?->id,
            invoiceId: $invoice->id,
            queueRendering: true
        );
    }

    /**
     * Render design to PNG (synchronous)
     *
     * @param MarketingDesign $design
     * @return MarketingDesign
     * @throws \Exception
     */
    public function renderDesign(MarketingDesign $design): MarketingDesign
    {
        if ($design->isCompleted()) {
            return $design;
        }

        $design->markAsRendering();
        $design->incrementRenderAttempts();

        try {
            $template = $design->template;
            $brandColors = [];

            // Get brand colors if brand kit is attached
            if ($design->brandKit) {
                $brandColors = [
                    'primary_color' => $design->brandKit->primary_color,
                    'secondary_color' => $design->brandKit->secondary_color,
                    'accent_color' => $design->brandKit->accent_color,
                ];
            }

            // Extract image prompt for DALL-E 3 or use design JSON for HTML engines
            $imagePrompt = $design->design_json['image_prompt'] ?? null;

            $result = $this->renderingService->renderToPng(
                designJson: $design->design_json,
                width: $template->width,
                height: $template->height,
                imagePrompt: $imagePrompt,
                brandColors: $brandColors
            );

            $design->markAsCompleted(
                renderedUrl: $result['url'],
                width: $result['width'],
                height: $result['height'],
                fileSize: $result['size']
            );

            Log::info('Design rendered successfully', [
                'design_id' => $design->id,
                'file_size' => $result['size'],
            ]);

            return $design->fresh();

        } catch (\Exception $e) {
            $design->markAsFailed($e->getMessage());

            Log::error('Design rendering failed', [
                'design_id' => $design->id,
                'error' => $e->getMessage(),
                'attempts' => $design->render_attempts,
            ]);

            throw $e;
        }
    }

    /**
     * Queue design for background rendering
     *
     * @param MarketingDesign $design
     * @param int $priority
     * @return GenerationQueue
     */
    public function queueForRendering(MarketingDesign $design, int $priority = 5): GenerationQueue
    {
        // Create queue entry for tracking
        $queueEntry = GenerationQueue::create([
            'design_id' => $design->id,
            'priority' => $priority,
            'status' => 'pending',
        ]);

        // Dispatch Laravel queue job
        RenderMarketingDesign::dispatch($design)
            ->onQueue(config('marketing.queue.queue_name', 'marketing'));

        Log::info('Design queued for rendering', [
            'design_id' => $design->id,
            'queue_entry_id' => $queueEntry->id,
            'priority' => $priority,
        ]);

        return $queueEntry;
    }

    /**
     * Process next design in queue
     *
     * @return MarketingDesign|null
     */
    public function processNextInQueue(): ?MarketingDesign
    {
        $queueEntry = GenerationQueue::pending()
            ->byPriority()
            ->first();

        if (!$queueEntry) {
            return null;
        }

        $queueEntry->markAsProcessing();
        $queueEntry->incrementAttempts();

        try {
            $design = $queueEntry->design;
            $this->renderDesign($design);

            $queueEntry->markAsCompleted();

            return $design;

        } catch (\Exception $e) {
            if ($queueEntry->maxAttemptsExceeded()) {
                $queueEntry->markAsFailed($e->getMessage());
            } else {
                // Reset to pending for retry
                $queueEntry->update(['status' => 'pending']);
            }

            throw $e;
        }
    }

    /**
     * Get user's design statistics
     *
     * @param User $user
     * @return array
     */
    public function getUserStats(User $user): array
    {
        return [
            'total_designs' => MarketingDesign::where('user_id', $user->id)->count(),
            'completed_designs' => MarketingDesign::where('user_id', $user->id)->completed()->count(),
            'rendering_designs' => MarketingDesign::where('user_id', $user->id)->rendering()->count(),
            'failed_designs' => MarketingDesign::where('user_id', $user->id)->failed()->count(),
            'total_downloads' => MarketingDesign::where('user_id', $user->id)->sum('download_count'),
            'designs_this_month' => MarketingDesign::where('user_id', $user->id)
                ->whereMonth('created_at', now()->month)
                ->count(),
        ];
    }

    /**
     * Delete design and cleanup files
     *
     * @param MarketingDesign $design
     * @return bool
     */
    public function deleteDesign(MarketingDesign $design): bool
    {
        // TODO: Delete rendered file from storage
        // Storage::disk(config('marketing.storage.disk'))->delete($design->rendered_path);

        return $design->delete();
    }

    /**
     * Validate create design request
     */
    protected function validateCreateDesign(
        ?User $user,
        string $prompt,
        int $templateId,
        ?int $brandKitId,
        ?int $invoiceId
    ): void {
        // Validate prompt length
        $maxLength = config('marketing.prompts.max_prompt_length');
        if (strlen($prompt) > $maxLength) {
            throw new \Exception("Prompt exceeds maximum length of {$maxLength} characters");
        }

        // Validate template exists and is active
        $template = MarketingTemplate::find($templateId);
        if (!$template) {
            throw new \Exception('Template not found');
        }

        if (!$template->isActive()) {
            throw new \Exception('Template is not active');
        }

        // Skip ownership validation for demo mode (no user)
        if (!$user) {
            if ($brandKitId || $invoiceId) {
                throw new \Exception('Demo mode does not support brand kits or invoices');
            }
            return;
        }

        // Validate brand kit ownership
        if ($brandKitId) {
            $brandKit = BrandKit::find($brandKitId);
            if (!$brandKit || $brandKit->user_id !== $user->id) {
                throw new \Exception('Brand kit not found or unauthorized');
            }
        }

        // Validate invoice ownership
        if ($invoiceId) {
            $invoice = Invoice::find($invoiceId);
            if (!$invoice || $invoice->user_id !== $user->id) {
                throw new \Exception('Invoice not found or unauthorized');
            }
        }
    }

    /**
     * Check user rate limit
     */
    protected function checkRateLimit(User $user): void
    {
        // Get user tier (from subscription or default to 'free')
        $tier = $user->subscription?->plan?->slug ?? 'free';

        $limits = config("marketing.rate_limits.{$tier}");

        // Check hourly limit
        $hourlyCount = MarketingDesign::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($hourlyCount >= $limits['per_hour']) {
            throw new \Exception('Hourly design generation limit exceeded');
        }

        // Check daily limit
        $dailyCount = MarketingDesign::where('user_id', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        if ($dailyCount >= $limits['per_day']) {
            throw new \Exception('Daily design generation limit exceeded');
        }

        // Check monthly limit
        $monthlyCount = MarketingDesign::where('user_id', $user->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        if ($monthlyCount >= $limits['per_month']) {
            throw new \Exception('Monthly design generation limit exceeded');
        }
    }

    /**
     * Generate title from prompt
     */
    protected function generateTitle(string $prompt, MarketingTemplate $template): string
    {
        // Take first 50 chars of prompt or template name
        $title = Str::limit($prompt, 50, '');

        if (empty(trim($title))) {
            $title = $template->name . ' - ' . now()->format('M d, Y');
        }

        return $title;
    }

    /**
     * Generate prompt from invoice
     */
    protected function generateInvoicePrompt(Invoice $invoice, ?string $customMessage): string
    {
        $amount = '₦' . number_format($invoice->total_amount, 2);
        $status = $invoice->status;

        if ($customMessage) {
            return $customMessage;
        }

        return match ($status) {
            'paid' => "Celebrate payment received for invoice {$invoice->invoice_number} - {$amount} from {$invoice->customer->name}. Thank you!",
            'sent', 'overdue' => "Remind customer about pending invoice {$invoice->invoice_number} - {$amount} due on {$invoice->due_date->format('M d, Y')}",
            default => "Share invoice {$invoice->invoice_number} - {$amount} for {$invoice->customer->name}",
        };
    }

    /**
     * Check if marketing feature is enabled
     */
    public function isEnabled(): bool
    {
        return config('marketing.enabled', true);
    }

    /**
     * Check if services are configured
     */
    public function isConfigured(): bool
    {
        return $this->claudeService->isConfigured() && $this->renderingService->isEngineAvailable();
    }
}
