<?php

namespace App\Filament\Pages;

use App\Services\AnalyticsService;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.dashboard';

    // Removed mount() to prevent potential errors
    // Analytics tracking moved to a lifecycle hook

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\WelcomePanel::class,
            \App\Filament\Widgets\StatsOverview::class,
            \App\Filament\Widgets\RevenueChartWidget::class,
            \App\Filament\Widgets\OverdueInvoicesWidget::class,
            \App\Filament\Widgets\RecentInvoices::class,
        ];
    }

    protected function trackDashboardView(): void
    {
        try {
            $user = auth()->user();
            if ($user) {
                $analytics = app(AnalyticsService::class);
                $analytics->track('dashboard_viewed', [
                    'user_has_business_profile' => $user->businessProfiles()->exists(),
                    'user_has_customer' => $user->customers()->exists(),
                    'invoices_count' => $user->invoices()->count(),
                    'user_id_hash' => hash('sha256', $user->id),
                ], null, $user->id);
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to track dashboard view: ' . $e->getMessage());
        }
    }
}
