<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class VerifyUserEmailCommand extends Command
{
    protected $signature = 'user:verify-email {email}';
    protected $description = 'Manually verify a user email address';

    public function handle(): int
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User not found: {$email}");
            return 1;
        }

        // Force update the verification timestamp
        $user->email_verified_at = now();
        $user->api_enabled = true;
        $user->save();

        // Refresh the model to get latest data
        $user->refresh();

        $this->info("✓ User verified successfully!");
        $this->newLine();
        $this->line("User ID: {$user->id}");
        $this->line("Name: {$user->name}");
        $this->line("Email: {$user->email}");
        $this->line("Verified At: {$user->email_verified_at}");
        $this->line("API Enabled: " . ($user->api_enabled ? 'Yes' : 'No'));
        $this->line("Updated At: {$user->updated_at}");

        return 0;
    }
}
