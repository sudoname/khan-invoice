<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class VerifyOAuthUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:verify-oauth';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark all OAuth users (Google/Facebook) as verified';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Verifying OAuth users...');

        // Find all OAuth users without email_verified_at
        $unverifiedOAuthUsers = User::whereNotNull('provider')
            ->whereNull('email_verified_at')
            ->get();

        if ($unverifiedOAuthUsers->isEmpty()) {
            $this->info('No unverified OAuth users found.');
            return 0;
        }

        $count = $unverifiedOAuthUsers->count();
        $this->info("Found {$count} unverified OAuth users.");

        // Mark them as verified
        foreach ($unverifiedOAuthUsers as $user) {
            $user->update(['email_verified_at' => now()]);
            $this->line("✓ Verified: {$user->name} ({$user->email}) - Provider: {$user->provider}");
        }

        $this->info("\n✓ Successfully verified {$count} OAuth users!");
        return 0;
    }
}
