<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreAnalyticsEventRequest;
use App\Models\AnalyticsEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnalyticsEventController extends Controller
{
    /**
     * Store analytics events in bulk
     */
    public function store(StoreAnalyticsEventRequest $request): JsonResponse
    {
        try {
            $events = $request->validated()['events'];
            $ipHash = $this->hashIp($request->ip());
            $userAgent = $request->userAgent();
            $userId = auth()->id(); // Will be null for unauthenticated users

            // Prepare events for bulk insert
            $eventsToInsert = [];
            $now = now();

            foreach ($events as $event) {
                $eventsToInsert[] = [
                    'name' => $event['name'],
                    'occurred_at' => \Carbon\Carbon::createFromTimestamp($event['ts']),
                    'path' => $event['path'] ?? null,
                    'referrer' => $event['referrer'] ?? null,
                    'utm_source' => $event['utm']['source'] ?? null,
                    'utm_medium' => $event['utm']['medium'] ?? null,
                    'utm_campaign' => $event['utm']['campaign'] ?? null,
                    'utm_term' => $event['utm']['term'] ?? null,
                    'utm_content' => $event['utm']['content'] ?? null,
                    'session_id' => $event['session_id'] ?? null,
                    'anonymous_id' => $event['anonymous_id'] ?? null,
                    'user_id' => $userId,
                    'properties' => isset($event['properties']) ? json_encode($event['properties']) : null,
                    'ip_hash' => $ipHash,
                    'user_agent' => $userAgent,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Bulk insert for performance
            DB::table('analytics_events')->insert($eventsToInsert);

            return response()->json([
                'success' => true,
                'message' => 'Events tracked successfully',
                'count' => count($eventsToInsert),
            ], 201);

        } catch (\Exception $e) {
            Log::error('Analytics event tracking failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to track events',
            ], 500);
        }
    }

    /**
     * Hash IP address for privacy
     */
    private function hashIp(?string $ip): ?string
    {
        if (!$ip) {
            return null;
        }

        return hash('sha256', $ip . config('app.key'));
    }
}
