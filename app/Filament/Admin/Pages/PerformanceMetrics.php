<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PerformanceMetrics extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string $view = 'filament.admin.pages.performance-metrics';

    protected static ?string $navigationLabel = 'Performance Metrics';

    protected static ?string $title = 'System Performance Metrics';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 99;

    public $metrics = [];

    public function mount(): void
    {
        $this->loadMetrics();
    }

    protected function loadMetrics(): void
    {
        $this->metrics = [
            'database' => $this->getDatabaseMetrics(),
            'cache' => $this->getCacheMetrics(),
            'application' => $this->getApplicationMetrics(),
            'charts' => $this->getChartData(),
        ];
    }

    protected function getDatabaseMetrics(): array
    {
        $startTime = microtime(true);
        $tables = ['users', 'invoices', 'customers', 'public_invoices'];
        $tableCounts = [];

        foreach ($tables as $table) {
            try {
                $tableCounts[$table] = DB::table($table)->count();
            } catch (\Exception $e) {
                $tableCounts[$table] = 'Error';
            }
        }

        $queryTime = round((microtime(true) - $startTime) * 1000, 2);

        return [
            'table_counts' => $tableCounts,
            'query_time' => $queryTime . ' ms',
            'connection' => config('database.default'),
        ];
    }

    protected function getCacheMetrics(): array
    {
        $startTime = microtime(true);
        $testKey = 'performance_test_' . time();

        Cache::put($testKey, 'test_value', 10);
        $retrieved = Cache::get($testKey);
        Cache::forget($testKey);

        $cacheTime = round((microtime(true) - $startTime) * 1000, 2);

        return [
            'driver' => config('cache.default'),
            'write_read_time' => $cacheTime . ' ms',
            'status' => $retrieved === 'test_value' ? 'Working' : 'Error',
        ];
    }

    protected function getApplicationMetrics(): array
    {
        return [
            'php_version' => phpversion(),
            'laravel_version' => app()->version(),
            'environment' => config('app.env'),
            'debug_mode' => config('app.debug') ? 'Enabled' : 'Disabled',
            'timezone' => config('app.timezone'),
        ];
    }

    protected function getChartData(): array
    {
        return \App\Models\PerformanceLog::getLast24Hours();
    }

    public function refreshMetrics(): void
    {
        $this->loadMetrics();
        $this->dispatch('metrics-refreshed');
    }
}
