<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PerformanceLog extends Model
{
    protected $fillable = [
        'query_time',
        'cache_write_read_time',
        'recorded_at',
    ];

    protected $casts = [
        'query_time' => 'decimal:2',
        'cache_write_read_time' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    /**
     * Log current performance metrics
     */
    public static function logCurrentMetrics(): void
    {
        // Measure database query time
        $queryStart = microtime(true);
        DB::table('users')->count();
        $queryTime = round((microtime(true) - $queryStart) * 1000, 2);

        // Measure cache write/read time
        $cacheStart = microtime(true);
        $testKey = 'perf_test_' . time();
        Cache::put($testKey, 'test', 10);
        Cache::get($testKey);
        Cache::forget($testKey);
        $cacheTime = round((microtime(true) - $cacheStart) * 1000, 2);

        // Save the log
        self::create([
            'query_time' => $queryTime,
            'cache_write_read_time' => $cacheTime,
            'recorded_at' => now(),
        ]);

        // Clean up old logs (keep only last 24 hours)
        self::where('recorded_at', '<', now()->subHours(24))->delete();
    }

    /**
     * Get performance data for last 24 hours
     */
    public static function getLast24Hours(): array
    {
        $logs = self::where('recorded_at', '>=', now()->subHours(24))
            ->orderBy('recorded_at')
            ->get();

        return [
            'labels' => $logs->map(fn($log) => $log->recorded_at->format('H:i'))->toArray(),
            'query_times' => $logs->pluck('query_time')->toArray(),
            'cache_times' => $logs->pluck('cache_write_read_time')->toArray(),
        ];
    }
}
