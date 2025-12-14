<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class EnableApiForVerifiedUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:enable-api-for-verified';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enable API access for all verified users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Enabling API access for all verified users...');

        // Find all users with verified email but API disabled
        $users = User::whereNotNull('email_verified_at')
            ->where('api_enabled', false)
            ->get();

        if ($users->isEmpty()) {
            $this->info('No users found with verified email and disabled API access.');
            return 0;
        }

        $this->info("Found {$users->count()} verified users with disabled API access.");

        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        $updated = 0;
        foreach ($users as $user) {
            $user->update(['api_enabled' => true]);
            $updated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✓ Successfully enabled API access for {$updated} verified users.");

        return 0;
    }
}
