<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class VerifyExistingUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:verify-existing {--all : Verify all users regardless of registration time}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify existing users who registered but did not receive verification emails';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Verifying existing users...');

        // Build query for unverified users
        $query = User::whereNull('email_verified_at');

        // If --all flag not provided, only verify users registered more than 1 hour ago
        if (!$this->option('all')) {
            $query->where('created_at', '<', now()->subHour());
            $this->info('(Only verifying users registered more than 1 hour ago)');
        }

        $unverifiedUsers = $query->get();

        if ($unverifiedUsers->isEmpty()) {
            $this->info('No unverified users found.');
            return 0;
        }

        $count = $unverifiedUsers->count();
        $this->info("Found {$count} unverified users.");

        if (!$this->confirm('Do you want to verify these users?', true)) {
            $this->info('Operation cancelled.');
            return 0;
        }

        // Mark them as verified
        foreach ($unverifiedUsers as $user) {
            $user->update(['email_verified_at' => now()]);
            $provider = $user->provider ? " (Provider: {$user->provider})" : '';
            $this->line("✓ Verified: {$user->name} ({$user->email}){$provider}");
        }

        $this->info("\n✓ Successfully verified {$count} users!");
        return 0;
    }
}
