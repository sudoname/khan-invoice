<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class VerifyAllUsersCommand extends Command
{
    protected $signature = 'users:verify-all';
    protected $description = 'Verify all users in the system';

    public function handle(): int
    {
        $this->info('Verifying all users...');
        $this->newLine();

        $users = User::all();
        $updated = 0;

        foreach ($users as $user) {
            $wasUnverified = $user->email_verified_at === null;

            $user->email_verified_at = now();
            $user->api_enabled = true;
            $user->save();

            if ($wasUnverified) {
                $this->line("✓ Verified: {$user->name} <{$user->email}>");
                $updated++;
            }
        }

        $this->newLine();
        $this->info("Total users: {$users->count()}");
        $this->info("Newly verified: {$updated}");
        $this->info("All users are now verified!");

        return 0;
    }
}
