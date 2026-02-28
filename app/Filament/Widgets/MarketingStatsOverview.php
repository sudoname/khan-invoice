<?php

namespace App\Filament\Widgets;

use App\Models\MarketingDesign;
use App\Models\BrandKit;
use App\Models\MarketingTemplate;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MarketingStatsOverview extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // Filter by user
        $designsQuery = MarketingDesign::where('user_id', auth()->id());
        $brandKitsQuery = BrandKit::where('user_id', auth()->id());

        // Total designs created
        $totalDesigns = (clone $designsQuery)->count();

        // Designs by status
        $completedDesigns = (clone $designsQuery)->where('status', 'completed')->count();
        $renderingDesigns = (clone $designsQuery)->where('status', 'rendering')->count();
        $failedDesigns = (clone $designsQuery)->where('status', 'failed')->count();

        // This month's designs
        $thisMonthDesigns = (clone $designsQuery)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $lastMonthDesigns = (clone $designsQuery)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        // Calculate growth
        $designGrowth = $lastMonthDesigns > 0
            ? (($thisMonthDesigns - $lastMonthDesigns) / $lastMonthDesigns) * 100
            : ($thisMonthDesigns > 0 ? 100 : 0);

        // Download statistics
        $totalDownloads = (clone $designsQuery)->sum('download_count');
        $avgDownloadsPerDesign = $totalDesigns > 0 ? round($totalDownloads / $totalDesigns, 1) : 0;

        // Success rate
        $successRate = $totalDesigns > 0
            ? round(($completedDesigns / $totalDesigns) * 100, 1)
            : 0;

        // Brand kits
        $totalBrandKits = $brandKitsQuery->count();
        $defaultBrandKit = $brandKitsQuery->where('is_default', true)->first();

        // Most popular template
        $popularTemplate = MarketingTemplate::active()
            ->orderBy('usage_count', 'desc')
            ->first();

        // Storage used (in MB)
        $storageUsed = (clone $designsQuery)
            ->where('status', 'completed')
            ->sum('file_size') / (1024 * 1024); // Convert bytes to MB

        return [
            Stat::make('Total Designs', $totalDesigns)
                ->description($thisMonthDesigns . ' created this month')
                ->descriptionIcon($designGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color('info')
                ->chart($this->getDesignTrendChart($designsQuery)),

            Stat::make('Completed', $completedDesigns)
                ->description($renderingDesigns . ' rendering • ' . $failedDesigns . ' failed')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Success Rate', $successRate . '%')
                ->description($completedDesigns . ' of ' . $totalDesigns . ' successful')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($successRate >= 90 ? 'success' : ($successRate >= 70 ? 'warning' : 'danger')),

            Stat::make('Total Downloads', number_format($totalDownloads))
                ->description('Avg ' . $avgDownloadsPerDesign . ' per design')
                ->descriptionIcon('heroicon-m-arrow-down-tray')
                ->color('primary'),

            Stat::make('Brand Kits', $totalBrandKits)
                ->description($defaultBrandKit ? 'Default: ' . $defaultBrandKit->name : 'No default set')
                ->descriptionIcon('heroicon-m-paint-brush')
                ->color('warning'),

            Stat::make('Storage Used', number_format($storageUsed, 1) . ' MB')
                ->description($totalDesigns . ' files stored')
                ->descriptionIcon('heroicon-m-circle-stack')
                ->color('info'),
        ];
    }

    /**
     * Get design trend chart data for the last 7 days
     */
    private function getDesignTrendChart($query): array
    {
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = (clone $query)
                ->whereDate('created_at', $date->format('Y-m-d'))
                ->count();
            $data[] = $count;
        }

        return $data;
    }

    /**
     * Check if the widget should be visible
     */
    public static function canView(): bool
    {
        // Show widget if user has created at least one design
        return MarketingDesign::where('user_id', auth()->id())->exists();
    }
}
