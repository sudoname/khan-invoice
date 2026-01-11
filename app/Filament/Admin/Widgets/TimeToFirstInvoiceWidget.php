<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Invoice;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class TimeToFirstInvoiceWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        // Get time-to-first-invoice metrics
        $metrics = $this->calculateTimeToFirstInvoice();

        return [
            Stat::make('Median Time to 1st Invoice', $this->formatTime($metrics['median']))
                ->description('50% of users create first invoice within this time')
                ->descriptionIcon('heroicon-m-clock')
                ->color('success'),

            Stat::make('Average Time to 1st Invoice', $this->formatTime($metrics['average']))
                ->description('Mean time across all users')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),

            Stat::make('Users with Invoices', $metrics['users_with_invoices'] . ' / ' . $metrics['total_users'])
                ->description(round($metrics['conversion_rate'], 1) . '% conversion rate')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning'),

            Stat::make('< 1 Hour', $metrics['under_1_hour'])
                ->description('Users who created invoice within 1 hour')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('success'),

            Stat::make('1-24 Hours', $metrics['1_to_24_hours'])
                ->description('Users who created invoice within 1 day')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),

            Stat::make('> 24 Hours', $metrics['over_24_hours'])
                ->description('Users who took more than 1 day')
                ->descriptionIcon('heroicon-m-clock')
                ->color('danger'),
        ];
    }

    protected function calculateTimeToFirstInvoice(): array
    {
        // Get all users with their first invoice creation time
        $usersWithFirstInvoice = DB::table('users')
            ->join('invoices', 'users.id', '=', 'invoices.user_id')
            ->select(
                'users.id as user_id',
                'users.created_at as user_created_at',
                DB::raw('MIN(invoices.created_at) as first_invoice_at')
            )
            ->groupBy('users.id', 'users.created_at')
            ->get();

        $totalUsers = User::count();
        $usersWithInvoices = $usersWithFirstInvoice->count();

        // Calculate time differences in seconds
        $timeDifferences = $usersWithFirstInvoice->map(function ($user) {
            $userCreated = \Carbon\Carbon::parse($user->user_created_at);
            $firstInvoice = \Carbon\Carbon::parse($user->first_invoice_at);
            return $userCreated->diffInSeconds($firstInvoice);
        })->sort()->values();

        // Calculate metrics
        $median = $timeDifferences->isEmpty() ? 0 : $timeDifferences[$timeDifferences->count() / 2];
        $average = $timeDifferences->isEmpty() ? 0 : $timeDifferences->average();
        $conversionRate = $totalUsers > 0 ? ($usersWithInvoices / $totalUsers) * 100 : 0;

        // Calculate distribution
        $under1Hour = $timeDifferences->filter(fn($time) => $time < 3600)->count();
        $from1To24Hours = $timeDifferences->filter(fn($time) => $time >= 3600 && $time < 86400)->count();
        $over24Hours = $timeDifferences->filter(fn($time) => $time >= 86400)->count();

        return [
            'median' => $median,
            'average' => $average,
            'total_users' => $totalUsers,
            'users_with_invoices' => $usersWithInvoices,
            'conversion_rate' => $conversionRate,
            'under_1_hour' => $under1Hour,
            '1_to_24_hours' => $from1To24Hours,
            'over_24_hours' => $over24Hours,
        ];
    }

    protected function formatTime(float $seconds): string
    {
        if ($seconds < 60) {
            return round($seconds) . ' sec';
        } elseif ($seconds < 3600) {
            return round($seconds / 60) . ' min';
        } elseif ($seconds < 86400) {
            return round($seconds / 3600, 1) . ' hrs';
        } else {
            return round($seconds / 86400, 1) . ' days';
        }
    }

    public static function canView(): bool
    {
        // Only show to admin users
        return auth()->check() && auth()->user()->isAdmin();
    }
}
