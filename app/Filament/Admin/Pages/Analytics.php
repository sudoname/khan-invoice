<?php

namespace App\Filament\Admin\Pages;

use App\Models\AnalyticsEvent;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Reactive;

class Analytics extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.admin.pages.analytics';

    protected static ?string $navigationLabel = 'Analytics';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return true; // Allow all authenticated admin users
    }

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

        // Current period
        $currentQuery = AnalyticsEvent::where('occurred_at', '>=', $currentStart);
        if ($this->eventFilter) {
            $currentQuery = $currentQuery->where('name', $this->eventFilter);
        }

        $totalEvents = (clone $currentQuery)->count();
        $uniqueSessions = (clone $currentQuery)->whereNotNull('session_id')->distinct()->count('session_id');
        $uniqueUsers = (clone $currentQuery)->whereNotNull('user_id')->distinct()->count('user_id');

        // Previous period
        $previousQuery = AnalyticsEvent::whereBetween('occurred_at', [$previousStart, $previousEnd]);
        if ($this->eventFilter) {
            $previousQuery = $previousQuery->where('name', $this->eventFilter);
        }

        $prevTotalEvents = (clone $previousQuery)->count();
        $prevUniqueSessions = (clone $previousQuery)->whereNotNull('session_id')->distinct()->count('session_id');
        $prevUniqueUsers = (clone $previousQuery)->whereNotNull('user_id')->distinct()->count('user_id');

        // Calculate percentage changes
        $eventsChange = $prevTotalEvents > 0 ? (($totalEvents - $prevTotalEvents) / $prevTotalEvents) * 100 : 0;
        $sessionsChange = $prevUniqueSessions > 0 ? (($uniqueSessions - $prevUniqueSessions) / $prevUniqueSessions) * 100 : 0;
        $usersChange = $prevUniqueUsers > 0 ? (($uniqueUsers - $prevUniqueUsers) / $prevUniqueUsers) * 100 : 0;

        return [
            'total_events' => $totalEvents,
            'total_events_change' => round($eventsChange, 1),
            'unique_sessions' => $uniqueSessions,
            'unique_sessions_change' => round($sessionsChange, 1),
            'unique_users' => $uniqueUsers,
            'unique_users_change' => round($usersChange, 1),
        ];
    }

    public function getEventBreakdown(): array
    {
        $days = (int) $this->dateRange;
        $startDate = now()->subDays($days);

        return AnalyticsEvent::select('name', DB::raw('count(*) as count'))
            ->where('occurred_at', '>=', $startDate)
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

        $query = AnalyticsEvent::where('occurred_at', '>=', $startDate);

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
                    'user_id' => $event->user_id ?? 'anonymous',
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
            ->whereNotNull('path')
            ->groupBy('path')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->pluck('count', 'path')
            ->toArray();
    }

    public function getConversionFunnel(): array
    {
        $days = (int) $this->dateRange;
        $startDate = now()->subDays($days);

        $landingViews = AnalyticsEvent::where('occurred_at', '>=', $startDate)
            ->where('name', 'landing_page_viewed')
            ->count();

        $generatorViews = AnalyticsEvent::where('occurred_at', '>=', $startDate)
            ->where('name', 'invoice_generator_viewed')
            ->count();

        $invoicesGenerated = AnalyticsEvent::where('occurred_at', '>=', $startDate)
            ->where('name', 'invoice_generated')
            ->count();

        $pdfDownloads = AnalyticsEvent::where('occurred_at', '>=', $startDate)
            ->where('name', 'invoice_pdf_downloaded')
            ->count();

        $signupPromptShown = AnalyticsEvent::where('occurred_at', '>=', $startDate)
            ->where('name', 'post_invoice_signup_prompt_shown')
            ->count();

        $signupPromptClicked = AnalyticsEvent::where('occurred_at', '>=', $startDate)
            ->where('name', 'post_invoice_signup_prompt_clicked')
            ->count();

        return [
            'landing_views' => $landingViews,
            'generator_views' => $generatorViews,
            'invoices_generated' => $invoicesGenerated,
            'pdf_downloads' => $pdfDownloads,
            'signup_prompt_shown' => $signupPromptShown,
            'signup_prompt_clicked' => $signupPromptClicked,
        ];
    }

    public function getAvailableEvents(): array
    {
        return AnalyticsEvent::distinct('name')
            ->orderBy('name')
            ->pluck('name')
            ->toArray();
    }
}
