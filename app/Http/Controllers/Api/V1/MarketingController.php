<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BrandKit;
use App\Models\Invoice;
use App\Models\MarketingDesign;
use App\Models\MarketingTemplate;
use App\Services\AI\MarketingDesignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class MarketingController extends Controller
{
    protected MarketingDesignService $marketingService;

    public function __construct(MarketingDesignService $marketingService)
    {
        $this->marketingService = $marketingService;

        // Apply authentication and rate limiting
        $this->middleware('auth:sanctum');
        $this->middleware('throttle:marketing_generation')->only(['generate', 'fromInvoice']);
    }

    /**
     * Get all available templates
     *
     * GET /api/v1/marketing/templates
     */
    public function templates(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'category' => 'nullable|string',
                'aspect_ratio' => ['nullable', Rule::in(config('marketing.constraints.allowed_aspect_ratios'))],
                'is_premium' => 'nullable|boolean',
            ]);

            $query = MarketingTemplate::active();

            if ($category = $request->input('category')) {
                $query->byCategory($category);
            }

            if ($aspectRatio = $request->input('aspect_ratio')) {
                $query->byAspectRatio($aspectRatio);
            }

            if ($request->has('is_premium')) {
                $isPremium = $request->boolean('is_premium');
                $query->where('is_premium', $isPremium);
            }

            $templates = $query->orderBy('usage_count', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $templates,
                'meta' => [
                    'count' => $templates->count(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch marketing templates', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch templates',
            ], 500);
        }
    }

    /**
     * Generate marketing design from prompt
     *
     * POST /api/v1/marketing/generate
     * Body: {
     *   "prompt": "Create a product launch graphic",
     *   "template_id": 5,
     *   "brand_kit_id": 2,
     *   "queue_rendering": true
     * }
     */
    public function generate(Request $request): JsonResponse
    {
        if (!$this->marketingService->isEnabled()) {
            return response()->json([
                'error' => 'Marketing feature is disabled',
            ], 503);
        }

        $request->validate([
            'prompt' => [
                'required',
                'string',
                'min:10',
                'max:' . config('marketing.prompts.max_prompt_length'),
            ],
            'template_id' => 'required|exists:marketing_templates,id',
            'brand_kit_id' => 'nullable|exists:brand_kits,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'queue_rendering' => 'nullable|boolean',
        ]);

        $startTime = microtime(true);
        $user = $request->user();

        try {
            $design = $this->marketingService->createDesign(
                user: $user,
                prompt: $request->input('prompt'),
                templateId: $request->input('template_id'),
                brandKitId: $request->input('brand_kit_id'),
                invoiceId: $request->input('invoice_id'),
                queueRendering: $request->boolean('queue_rendering', true)
            );

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->logMarketingRequest('generate', $user->id, $duration);

            return response()->json([
                'success' => true,
                'data' => $design,
                'message' => 'Design created successfully' . ($design->status === 'draft' ? ' and queued for rendering' : ''),
                'meta' => [
                    'duration_ms' => $duration,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Marketing design generation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Generate design from invoice
     *
     * POST /api/v1/marketing/from-invoice/{invoice}
     * Body: {
     *   "template_id": 3,
     *   "message": "Payment received! Thank you"
     * }
     */
    public function fromInvoice(Request $request, Invoice $invoice): JsonResponse
    {
        if (!$this->marketingService->isEnabled()) {
            return response()->json([
                'error' => 'Marketing feature is disabled',
            ], 503);
        }

        // Ensure user owns the invoice
        if ($invoice->user_id !== $request->user()->id) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'template_id' => 'required|exists:marketing_templates,id',
            'message' => 'nullable|string|max:300',
        ]);

        $startTime = microtime(true);
        $user = $request->user();

        try {
            $design = $this->marketingService->createFromInvoice(
                invoice: $invoice,
                templateId: $request->input('template_id'),
                customMessage: $request->input('message')
            );

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->logMarketingRequest('from_invoice', $user->id, $duration);

            return response()->json([
                'success' => true,
                'data' => $design,
                'message' => 'Invoice share graphic created successfully',
                'meta' => [
                    'invoice_id' => $invoice->id,
                    'duration_ms' => $duration,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Invoice share graphic generation failed', [
                'user_id' => $user->id,
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get user's marketing designs
     *
     * GET /api/v1/marketing/designs
     */
    public function designs(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['nullable', Rule::in(['draft', 'rendering', 'completed', 'failed'])],
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $user = $request->user();
        $query = MarketingDesign::where('user_id', $user->id);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $perPage = $request->input('per_page', 15);
        $designs = $query->with(['template', 'brandKit', 'invoice'])
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $designs->items(),
            'meta' => [
                'current_page' => $designs->currentPage(),
                'per_page' => $designs->perPage(),
                'total' => $designs->total(),
                'last_page' => $designs->lastPage(),
            ],
        ]);
    }

    /**
     * Get specific design
     *
     * GET /api/v1/marketing/designs/{design}
     */
    public function show(Request $request, MarketingDesign $design): JsonResponse
    {
        // Ensure user owns the design
        if ($design->user_id !== $request->user()->id) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 403);
        }

        $design->load(['template', 'brandKit', 'invoice']);

        return response()->json([
            'success' => true,
            'data' => $design,
        ]);
    }

    /**
     * Delete design
     *
     * DELETE /api/v1/marketing/designs/{design}
     */
    public function destroy(Request $request, MarketingDesign $design): JsonResponse
    {
        // Ensure user owns the design
        if ($design->user_id !== $request->user()->id) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 403);
        }

        try {
            $this->marketingService->deleteDesign($design);

            return response()->json([
                'success' => true,
                'message' => 'Design deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete design', [
                'design_id' => $design->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to delete design',
            ], 500);
        }
    }

    /**
     * Get user's brand kits
     *
     * GET /api/v1/marketing/brand-kits
     */
    public function brandKits(Request $request): JsonResponse
    {
        $user = $request->user();

        $brandKits = BrandKit::where('user_id', $user->id)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $brandKits,
            'meta' => [
                'count' => $brandKits->count(),
            ],
        ]);
    }

    /**
     * Create brand kit
     *
     * POST /api/v1/marketing/brand-kits
     * Body: {
     *   "name": "My Brand",
     *   "primary_color": "#6366F1",
     *   "secondary_color": "#8B5CF6",
     *   "accent_color": "#F59E0B",
     *   "font_heading": "Inter",
     *   "font_body": "Inter",
     *   "logo_url": "https://...",
     *   "is_default": false
     * }
     */
    public function storeBrandKit(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'logo_url' => 'nullable|url|max:500',
            'primary_color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'secondary_color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'accent_color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'font_heading' => 'nullable|string|max:100',
            'font_body' => 'nullable|string|max:100',
            'is_default' => 'nullable|boolean',
        ]);

        $user = $request->user();

        try {
            $brandKit = BrandKit::create([
                'user_id' => $user->id,
                'name' => $request->input('name'),
                'logo_url' => $request->input('logo_url'),
                'primary_color' => $request->input('primary_color'),
                'secondary_color' => $request->input('secondary_color'),
                'accent_color' => $request->input('accent_color'),
                'font_heading' => $request->input('font_heading', 'Inter'),
                'font_body' => $request->input('font_body', 'Inter'),
                'is_default' => $request->boolean('is_default', false),
            ]);

            return response()->json([
                'success' => true,
                'data' => $brandKit,
                'message' => 'Brand kit created successfully',
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to create brand kit', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to create brand kit',
            ], 500);
        }
    }

    /**
     * Update brand kit
     *
     * PUT /api/v1/marketing/brand-kits/{brandKit}
     */
    public function updateBrandKit(Request $request, BrandKit $brandKit): JsonResponse
    {
        // Ensure user owns the brand kit
        if ($brandKit->user_id !== $request->user()->id) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:100',
            'logo_url' => 'nullable|url|max:500',
            'primary_color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'secondary_color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'accent_color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'font_heading' => 'nullable|string|max:100',
            'font_body' => 'nullable|string|max:100',
            'is_default' => 'nullable|boolean',
        ]);

        try {
            $brandKit->update($request->only([
                'name',
                'logo_url',
                'primary_color',
                'secondary_color',
                'accent_color',
                'font_heading',
                'font_body',
                'is_default',
            ]));

            return response()->json([
                'success' => true,
                'data' => $brandKit->fresh(),
                'message' => 'Brand kit updated successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update brand kit', [
                'brand_kit_id' => $brandKit->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to update brand kit',
            ], 500);
        }
    }

    /**
     * Delete brand kit
     *
     * DELETE /api/v1/marketing/brand-kits/{brandKit}
     */
    public function destroyBrandKit(Request $request, BrandKit $brandKit): JsonResponse
    {
        // Ensure user owns the brand kit
        if ($brandKit->user_id !== $request->user()->id) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 403);
        }

        try {
            $brandKit->delete();

            return response()->json([
                'success' => true,
                'message' => 'Brand kit deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete brand kit', [
                'brand_kit_id' => $brandKit->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to delete brand kit',
            ], 500);
        }
    }

    /**
     * Get user statistics
     *
     * GET /api/v1/marketing/stats
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        try {
            $stats = $this->marketingService->getUserStats($user);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch marketing stats', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch statistics',
            ], 500);
        }
    }

    /**
     * Track design download
     *
     * POST /api/v1/marketing/designs/{design}/download
     */
    public function trackDownload(Request $request, MarketingDesign $design): JsonResponse
    {
        // Ensure user owns the design
        if ($design->user_id !== $request->user()->id) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 403);
        }

        if (!$design->isCompleted()) {
            return response()->json([
                'error' => 'Design is not completed yet',
            ], 400);
        }

        try {
            $design->incrementDownloadCount();

            return response()->json([
                'success' => true,
                'data' => [
                    'download_count' => $design->download_count,
                    'download_url' => $design->rendered_url,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to track download', [
                'design_id' => $design->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to track download',
            ], 500);
        }
    }

    /**
     * Log marketing request for monitoring
     */
    protected function logMarketingRequest(string $action, int $userId, float $duration): void
    {
        if (!config('marketing.analytics.enabled')) {
            return;
        }

        Log::channel('daily')->info('Marketing request', [
            'action' => $action,
            'user_id' => $userId,
            'duration_ms' => $duration,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
