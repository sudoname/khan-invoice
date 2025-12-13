<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimit
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Check if API is enabled
        if (!$user->api_enabled) {
            return response()->json([
                'message' => 'API access is disabled for your account',
            ], 403);
        }

        // Rate limiting logic
        $limit = $user->api_rate_limit ?? 60; // default 60 requests per minute
        $key = 'api_rate_limit_' . $user->id;

        $requests = Cache::get($key, 0);

        if ($requests >= $limit) {
            return response()->json([
                'message' => 'Rate limit exceeded. Maximum ' . $limit . ' requests per minute allowed.',
                'retry_after' => 60,
            ], 429);
        }

        Cache::put($key, $requests + 1, now()->addMinute());

        // Update last used timestamp
        $user->update(['api_last_used_at' => now()]);

        $response = $next($request);

        // Add rate limit headers
        $response->headers->set('X-RateLimit-Limit', $limit);
        $response->headers->set('X-RateLimit-Remaining', max(0, $limit - $requests - 1));
        $response->headers->set('X-RateLimit-Reset', now()->addMinute()->timestamp);

        return $response;
    }
}
