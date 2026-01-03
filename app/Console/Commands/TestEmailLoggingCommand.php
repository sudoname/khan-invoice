<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmailLoggingCommand extends Command
{
    protected $signature = 'test:email {email} {--subject=Test Email}';
    protected $description = 'Send a test email to verify email logging';

    public function handle(): int
    {
        $email = $this->argument('email');
        $subject = $this->option('subject');

        $this->info("Sending test email to {$email}...");

        try {
            Mail::raw('This is a test email to verify email logging is working correctly.', function ($message) use ($email, $subject) {
                $message->to($email)
                    ->subject($subject);
            });

            $this->info('✓ Email sent successfully!');
            $this->newLine();
            $this->info('Check the email_logs table to verify it was logged:');
            $this->line('  SELECT * FROM email_logs ORDER BY id DESC LIMIT 1;');

            return 0;
        } catch (\Exception $e) {
            $this->error('✗ Failed to send email: ' . $e->getMessage());
            return 1;
        }
    }
}
