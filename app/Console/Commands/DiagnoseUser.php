<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class DiagnoseUser extends Command
{
    protected $signature = 'user:diagnose {email}';
    protected $description = 'Diagnose user dashboard issues';

    public function handle()
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User not found: {$email}");
            return 1;
        }

        $this->info("User Diagnostic Report");
        $this->line("========================");
        $this->line("Name: {$user->name}");
        $this->line("Email: {$user->email}");
        $this->line("Created: {$user->created_at}");
        $this->line("Account Age: " . $user->created_at->diffForHumans());
        $this->line("");

        $invoiceCount = $user->invoices()->count();
        $this->line("Invoices: {$invoiceCount}");
        $this->line("Business Profiles: " . $user->businessProfiles()->count());
        $this->line("Customers: " . $user->customers()->count());
        $this->line("");

        $settings = $user->settings ?? [];
        $dismissed = $settings['welcome_dismissed'] ?? false;
        $this->line("Welcome Panel Dismissed: " . ($dismissed ? 'YES' : 'NO'));
        $this->line("");

        // Check WelcomePanel visibility logic
        $this->info("WelcomePanel Visibility Check:");
        $this->line("- Has dismissed: " . ($dismissed ? 'YES (will hide)' : 'NO'));
        $this->line("- Has 0 invoices: " . ($invoiceCount === 0 ? 'YES (will show)' : 'NO'));
        $this->line("- Account < 7 days old: " . ($user->created_at->isAfter(now()->subDays(7)) ? 'YES (will show)' : 'NO'));
        $this->line("");

        $shouldShow = !$dismissed && ($invoiceCount === 0 || $user->created_at->isAfter(now()->subDays(7)));
        $this->line("WelcomePanel SHOULD SHOW: " . ($shouldShow ? 'YES' : 'NO'));
        $this->line("");

        if (!$shouldShow && !$dismissed) {
            $this->warn("Dashboard will be empty if user has no widgets to show!");
            $this->info("Suggestion: Visit /app?welcome=1 to force show welcome panel");
        }

        if ($dismissed) {
            $this->info("To reset welcome panel, run:");
            $this->line("php artisan user:reset-welcome {$email}");
        }

        return 0;
    }
}
