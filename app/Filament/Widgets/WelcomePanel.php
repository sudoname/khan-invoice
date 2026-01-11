<?php

namespace App\Filament\Widgets;

use App\Services\AbTestingService;
use Filament\Widgets\Widget;

class WelcomePanel extends Widget
{
    protected static string $view = 'filament.widgets.welcome-panel';

    protected int | string | array $columnSpan = 'full';

    public string $abTestVariant;

    public function mount(): void
    {
        // Assign A/B test variant for dashboard CTA
        $abTestService = app(AbTestingService::class);

        // Only run A/B test if test is active
        if ($abTestService->isTestActive('dashboard_cta')) {
            $this->abTestVariant = $abTestService->getVariant(
                'dashboard_cta',
                ['control', 'variant_quick_only'], // control = dual CTA, variant = quick only
                [50, 50] // 50/50 split
            );
        } else {
            // Default to control if test not active
            $this->abTestVariant = 'control';
        }
    }

    public static function canView(): bool
    {
        $user = auth()->user();

        // Show if explicitly requested via query param
        if (request()->has('welcome') && request()->get('welcome') == '1') {
            return true;
        }

        // Check if user has dismissed the welcome panel
        $dismissed = $user->settings['welcome_dismissed'] ?? false;

        if ($dismissed) {
            return false;
        }

        // Always show for users with no invoices (onboarding experience)
        if ($user->invoices()->count() === 0) {
            return true;
        }

        // Show if user is new (registered within last 7 days)
        return $user->created_at->isAfter(now()->subDays(7));
    }

    public function dismiss()
    {
        $user = auth()->user();
        $settings = $user->settings ?? [];
        $settings['welcome_dismissed'] = true;
        $user->settings = $settings;
        $user->save();

        // Track analytics
        if (class_exists('App\Services\AnalyticsService')) {
            app('App\Services\AnalyticsService')->track('registered_welcome_dismissed');
        }

        // Refresh the page to hide the panel
        return redirect()->to('/app');
    }
}
