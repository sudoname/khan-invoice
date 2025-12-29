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

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 1;

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
        $startDate = now()->subDays($days);

        $baseQuery = AnalyticsEvent::where('occurred_at', '>=', $startDate);

        if ($this->eventFilter) {
            $baseQuery = $baseQuery->where('name', $this->eventFilter);
        }

        $totalEvents = (clone $baseQuery)->count();
        $uniqueSessions = (clone $baseQuery)->whereNotNull('session_id')->distinct()->count('session_id');
        $uniqueUsers = (clone $baseQuery)->whereNotNull('user_id')->distinct()->count('user_id');

        return [
            'total_events' => $totalEvents,
            'unique_sessions' => $uniqueSessions,
            'unique_users' => $uniqueUsers,
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
