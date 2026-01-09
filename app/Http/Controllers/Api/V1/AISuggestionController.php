<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AI\InsightsService;
use App\Services\AI\ReminderPlannerService;
use App\Services\AI\SuggestionService;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AISuggestionController extends Controller
{
    protected SuggestionService $suggestionService;
    protected ReminderPlannerService $reminderService;
    protected InsightsService $insightsService;

    public function __construct(
        SuggestionService $suggestionService,
        ReminderPlannerService $reminderService,
        InsightsService $insightsService
    ) {
        $this->suggestionService = $suggestionService;
        $this->reminderService = $reminderService;
        $this->insightsService = $insightsService;

        // Apply rate limiting middleware
        $this->middleware('throttle:ai_suggestions')->only(['suggestCustomers', 'suggestItems', 'suggestDueDate']);
        $this->middleware('throttle:ai_reminders')->only(['planReminders', 'createReminderPlan']);
        $this->middleware('throttle:ai_insights')->only(['getInsights']);
    }

    /**
     * Suggest customers based on query
     *
     * GET /api/v1/ai/suggest/customers?q=search_term
     */
    public function suggestCustomers(Request $request): JsonResponse
    {
        if (!config('kinvoice.ai.suggestions.enabled')) {
            return response()->json([
                'error' => 'Customer suggestions are disabled',
            ], 503);
        }

        $request->validate([
            'q' => 'nullable|string|min:' . config('kinvoice.ai.suggestions.min_query_length'),
        ]);

        $startTime = microtime(true);
        $user = $request->user();
        $query = $request->input('q', '');

        try {
            $suggestions = $this->suggestionService->suggestCustomers($user, $query);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->logAIRequest('customer_suggestions', $user->id, $duration, $suggestions->count());

            return response()->json([
                'success' => true,
                'data' => $suggestions,
                'meta' => [
                    'count' => $suggestions->count(),
                    'query' => $query,
                    'duration_ms' => $duration,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AI suggestion error', [
                'type' => 'customers',
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch customer suggestions',
            ], 500);
        }
    }

    /**
     * Suggest line items based on query and optional customer
     *
     * GET /api/v1/ai/suggest/items?q=search_term&customer_id=123
     */
    public function suggestItems(Request $request): JsonResponse
    {
        if (!config('kinvoice.ai.suggestions.enabled')) {
            return response()->json([
                'error' => 'Item suggestions are disabled',
            ], 503);
        }

        $request->validate([
            'q' => 'nullable|string|min:' . config('kinvoice.ai.suggestions.min_query_length'),
            'customer_id' => 'nullable|integer|exists:customers,id',
        ]);

        $startTime = microtime(true);
        $user = $request->user();
        $query = $request->input('q', '');
        $customerId = $request->input('customer_id');

        try {
            $suggestions = $this->suggestionService->suggestItems($user, $query, $customerId);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->logAIRequest('item_suggestions', $user->id, $duration, $suggestions->count());

            return response()->json([
                'success' => true,
                'data' => $suggestions,
                'meta' => [
                    'count' => $suggestions->count(),
                    'query' => $query,
                    'customer_id' => $customerId,
                    'duration_ms' => $duration,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AI suggestion error', [
                'type' => 'items',
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch item suggestions',
            ], 500);
        }
    }

    /**
     * Suggest due date based on user/customer history
     *
     * GET /api/v1/ai/suggest/due-date?customer_id=123
     */
    public function suggestDueDate(Request $request): JsonResponse
    {
        if (!config('kinvoice.ai.suggestions.enabled')) {
            return response()->json([
                'error' => 'Due date suggestions are disabled',
            ], 503);
        }

        $request->validate([
            'customer_id' => 'nullable|integer|exists:customers,id',
        ]);

        $startTime = microtime(true);
        $user = $request->user();
        $customerId = $request->input('customer_id');

        try {
            $suggestion = $this->suggestionService->suggestDueDate($user, $customerId);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->logAIRequest('due_date_suggestion', $user->id, $duration, 1);

            return response()->json([
                'success' => true,
                'data' => $suggestion,
                'meta' => [
                    'customer_id' => $customerId,
                    'duration_ms' => $duration,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AI suggestion error', [
                'type' => 'due_date',
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch due date suggestion',
            ], 500);
        }
    }

    /**
     * Plan reminders for an invoice (preview only)
     *
     * GET /api/v1/ai/reminders/plan/{invoice}
     */
    public function planReminders(Invoice $invoice): JsonResponse
    {
        if (!config('kinvoice.ai.reminders.enabled')) {
            return response()->json([
                'error' => 'Payment reminders are disabled',
            ], 503);
        }

        // Ensure user owns the invoice
        if ($invoice->user_id !== auth()->id()) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 403);
        }

        $startTime = microtime(true);

        try {
            $plan = $this->reminderService->plan($invoice);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->logAIRequest('reminder_plan', auth()->id(), $duration, $plan->count());

            return response()->json([
                'success' => true,
                'data' => $plan,
                'meta' => [
                    'invoice_id' => $invoice->id,
                    'reminder_count' => $plan->count(),
                    'duration_ms' => $duration,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AI reminder planning error', [
                'invoice_id' => $invoice->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to plan reminders',
            ], 500);
        }
    }

    /**
     * Create and persist reminder plan for an invoice
     *
     * POST /api/v1/ai/reminders/{invoice}
     * Body: { "channel": "email" }
     */
    public function createReminderPlan(Request $request, Invoice $invoice): JsonResponse
    {
        if (!config('kinvoice.ai.reminders.enabled')) {
            return response()->json([
                'error' => 'Payment reminders are disabled',
            ], 503);
        }

        // Ensure user owns the invoice
        if ($invoice->user_id !== auth()->id()) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'channel' => 'required|in:email,whatsapp,sms',
        ]);

        $channel = $request->input('channel');
        $startTime = microtime(true);

        try {
            $reminders = $this->reminderService->persistPlan($invoice, $channel);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->logAIRequest('reminder_creation', auth()->id(), $duration, $reminders->count());

            return response()->json([
                'success' => true,
                'data' => $reminders,
                'message' => "Created {$reminders->count()} payment reminders",
                'meta' => [
                    'invoice_id' => $invoice->id,
                    'channel' => $channel,
                    'reminder_count' => $reminders->count(),
                    'duration_ms' => $duration,
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('AI reminder creation error', [
                'invoice_id' => $invoice->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to create reminder plan',
            ], 500);
        }
    }

    /**
     * Get analytics insights
     *
     * GET /api/v1/ai/insights
     */
    public function getInsights(Request $request): JsonResponse
    {
        if (!config('kinvoice.ai.insights.enabled')) {
            return response()->json([
                'error' => 'Insights are disabled',
            ], 503);
        }

        $startTime = microtime(true);
        $user = $request->user();

        try {
            $insights = $this->insightsService->getAllInsights($user);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->logAIRequest('insights', $user->id, $duration, 1);

            return response()->json([
                'success' => true,
                'data' => $insights,
                'meta' => [
                    'duration_ms' => $duration,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AI insights error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch insights',
            ], 500);
        }
    }

    /**
     * Get suggestion statistics
     *
     * GET /api/v1/ai/stats
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $user = $request->user();

        try {
            $stats = $this->suggestionService->getStatistics($user);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('AI statistics error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch statistics',
            ], 500);
        }
    }

    /**
     * Log AI request for monitoring
     */
    protected function logAIRequest(string $endpoint, int $userId, float $duration, int $resultCount): void
    {
        if (!config('kinvoice.ai.logging.enabled')) {
            return;
        }

        if (!config('kinvoice.ai.logging.log_metadata_only')) {
            return;
        }

        Log::channel(config('kinvoice.ai.logging.channel'))->info('AI request', [
            'endpoint' => $endpoint,
            'user_id' => $userId,
            'duration_ms' => $duration,
            'result_count' => $resultCount,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
