<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Console\Command;

class SendWelcomeEmailsCommand extends Command
{
    protected $signature = 'emails:send-welcome {--user-ids=}';
    protected $description = 'Send welcome emails to specific users';

    public function handle(): int
    {
        $userIds = $this->option('user-ids');

        if ($userIds) {
            $userIdsArray = explode(',', $userIds);
            $users = User::whereIn('id', $userIdsArray)->get();
        } else {
            $this->error('Please provide --user-ids option (comma-separated)');
            return 1;
        }

        if ($users->isEmpty()) {
            $this->error('No users found with provided IDs');
            return 1;
        }

        $count = 0;
        foreach ($users as $user) {
            try {
                $user->notify(new WelcomeNotification($user));
                $this->info("✓ Sent welcome email to: {$user->name} <{$user->email}>");
                $count++;
            } catch (\Exception $e) {
                $this->error("✗ Failed to send to {$user->email}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Sent {$count} welcome email(s) successfully!");

        return 0;
    }
}
