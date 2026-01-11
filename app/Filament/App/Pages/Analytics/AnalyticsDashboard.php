<?php

namespace App\Filament\App\Pages\Analytics;

use App\Models\AnalyticsEvent;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class AnalyticsDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.app.pages.analytics.analytics-dashboard';

    protected static ?string $navigationLabel = 'Analytics';

    protected static ?int $navigationSort = 10;

    public string $dateRange = '7';

    public ?string $eventFilter = null;

    public function mount(): void
    {
        // Initialize filters
        $this->dateRange = '7';
    }

    public function getStats(): array
    {
        $days = (int) $this->dateRange;
        $currentStart = now()->subDays($days);
        $previousStart = now()->subDays($days * 2);
        $previousEnd = $currentStart;

        // Current period - filter by current user
        $currentQuery = AnalyticsEvent::where('occurred_at', '>=', $currentStart)
            ->where('user_id', auth()->id());

        if ($this->eventFilter) {
            $currentQuery = $currentQuery->where('name', $this->eventFilter);
        }

        $totalEvents = (clone $currentQuery)->count();
        $uniqueSessions = (clone $currentQuery)->whereNotNull('session_id')->distinct()->count('session_id');

        // Previous period - filter by current user
        $previousQuery = AnalyticsEvent::whereBetween('occurred_at', [$previousStart, $previousEnd])
            ->where('user_id', auth()->id());

        if ($this->eventFilter) {
            $previousQuery = $previousQuery->where('name', $this->eventFilter);
        }

        $prevTotalEvents = (clone $previousQuery)->count();
        $prevUniqueSessions = (clone $previousQuery)->whereNotNull('session_id')->distinct()->count('session_id');

        // Calculate percentage changes
        $eventsChange = $prevTotalEvents > 0 ? (($totalEvents - $prevTotalEvents) / $prevTotalEvents) * 100 : 0;
        $sessionsChange = $prevUniqueSessions > 0 ? (($uniqueSessions - $prevUniqueSessions) / $prevUniqueSessions) * 100 : 0;

        return [
            'total_events' => $totalEvents,
            'total_events_change' => round($eventsChange, 1),
            'unique_sessions' => $uniqueSessions,
            'unique_sessions_change' => round($sessionsChange, 1),
        ];
    }

    public function getEventBreakdown(): array
    {
        $days = (int) $this->dateRange;
        $startDate = now()->subDays($days);

        return AnalyticsEvent::select('name', DB::raw('count(*) as count'))
            ->where('occurred_at', '>=', $startDate)
            ->where('user_id', auth()->id())
            ->groupBy('name')
            ->orderBy('count', 'desc')
            ->limit(20)
            ->get()
            ->pluck('count', 'name')
            ->toArray();
    }

    public function getRecentEvents(): array
    {
        $days = (int) $this->dateRange;
        $startDate = now()->subDays($days);

        $query = AnalyticsEvent::where('occurred_at', '>=', $startDate)
            ->where('user_id', auth()->id());

        if ($this->eventFilter) {
            $query->where('name', $this->eventFilter);
        }

        return $query->orderBy('occurred_at', 'desc')
            ->limit(100)
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'name' => $event->name,
                    'occurred_at' => $event->occurred_at->format('Y-m-d H:i:s'),
                    'path' => $event->path,
                    'session_id' => substr($event->session_id ?? '', 0, 8) . '...',
                    'properties' => $event->properties ? json_encode($event->properties) : null,
                ];
            })
            ->toArray();
    }

    public function getTopPaths(): array
    {
        $days = (int) $this->dateRange;
        $startDate = now()->subDays($days);

        return AnalyticsEvent::select('path', DB::raw('count(*) as count'))
            ->where('occurred_at', '>=', $startDate)
            ->where('user_id', auth()->id())
            ->whereNotNull('path')
            ->groupBy('path')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->pluck('count', 'path')
            ->toArray();
    }

    public function getUserActivityFunnel(): array
    {
        $days = (int) $this->dateRange;
        $startDate = now()->subDays($days);

        $dashboardViewed = AnalyticsEvent::where('occurred_at', '>=', $startDate)
            ->where('user_id', auth()->id())
            ->where('name', 'dashboard_viewed')
            ->count();

        $invoiceStarted = AnalyticsEvent::where('occurred_at', '>=', $startDate)
            ->where('user_id', auth()->id())
            ->whereIn('name', ['quick_invoice_started', 'advanced_invoice_started'])
            ->count();

        $invoiceCreated = AnalyticsEvent::where('occurred_at', '>=', $startDate)
            ->where('user_id', auth()->id())
            ->where('name', 'invoice_created')
            ->count();

        $invoiceShared = AnalyticsEvent::where('occurred_at', '>=', $startDate)
            ->where('user_id', auth()->id())
            ->where('name', 'invoice_share_clicked')
            ->count();

        $pdfDownloaded = AnalyticsEvent::where('occurred_at', '>=', $startDate)
            ->where('user_id', auth()->id())
            ->where('name', 'invoice_pdf_downloaded')
            ->count();

        return [
            'dashboard_viewed' => $dashboardViewed,
            'invoice_started' => $invoiceStarted,
            'invoice_created' => $invoiceCreated,
            'invoice_shared' => $invoiceShared,
            'pdf_downloaded' => $pdfDownloaded,
        ];
    }

    public function getAvailableEvents(): array
    {
        return AnalyticsEvent::where('user_id', auth()->id())
            ->distinct('name')
            ->orderBy('name')
            ->pluck('name')
            ->toArray();
    }
}
