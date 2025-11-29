<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmail extends Command
{
    protected $signature = 'email:test {email?}';
    protected $description = 'Send a test email to verify email configuration';

    public function handle()
    {
        $email = $this->argument('email') ?? config('mail.from.address');

        $this->info('Sending test email to: ' . $email);

        try {
            Mail::raw('This is a test email from Khan Invoice. Your email configuration is working correctly!', function ($message) use ($email) {
                $message->to($email)
                    ->subject('Khan Invoice - Email Configuration Test');
            });

            $this->info('✓ Test email sent successfully!');
            $this->info('Please check your inbox at: ' . $email);

            return 0;
        } catch (\Exception $e) {
            $this->error('✗ Failed to send email: ' . $e->getMessage());
            return 1;
        }
    }
}
