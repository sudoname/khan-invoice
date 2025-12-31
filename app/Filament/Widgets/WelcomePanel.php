<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class WelcomePanel extends Widget
{
    protected static string $view = 'filament.widgets.welcome-panel';

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        // Show if explicitly requested via query param
        if (request()->has('welcome') && request()->get('welcome') == '1') {
            return true;
        }

        // Check if user has dismissed the welcome panel
        $dismissed = $user->settings['welcome_dismissed'] ?? false;

        // Show if never dismissed and user is new (registered within last 7 days)
        return !$dismissed && $user->created_at->isAfter(now()->subDays(7));
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
