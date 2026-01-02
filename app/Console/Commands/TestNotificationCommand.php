<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\TestSmsWhatsAppNotification;
use Illuminate\Console\Command;

class TestNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'notifications:test {user_id} {phone} {--channel=all}';

    /**
     * The console command description.
     */
    protected $description = 'Test SMS and WhatsApp notifications';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userId = $this->argument('user_id');
        $phone = $this->argument('phone');
        $channel = $this->option('channel');

        $user = User::find($userId);

        if (!$user) {
            $this->error("User with ID {$userId} not found.");
            return 1;
        }

        $this->info("Sending test notification to {$phone}...");

        try {
            $user->notify(new TestSmsWhatsAppNotification($phone, $channel));
            $this->info("Test notification sent successfully!");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to send notification: " . $e->getMessage());
            return 1;
        }
    }
}
