<?php

namespace App\Console\Commands;

use App\Services\TermiiService;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class TestSmsWhatsAppCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'test:messaging {phone} {--sms} {--whatsapp}';

    /**
     * The console command description.
     */
    protected $description = 'Test SMS and WhatsApp messaging services';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $phone = $this->argument('phone');
        $testSms = $this->option('sms');
        $testWhatsApp = $this->option('whatsapp');

        // If no specific option, test both
        if (!$testSms && !$testWhatsApp) {
            $testSms = true;
            $testWhatsApp = true;
        }

        $this->info("Testing messaging services to: {$phone}");
        $this->newLine();

        // Test SMS
        if ($testSms) {
            $this->info('Testing SMS...');
            try {
                $termiiService = app(TermiiService::class);
                $message = sprintf(
                    'TEST: This is a test SMS from %s. Your SMS notifications are working!',
                    config('app.name')
                );

                $result = $termiiService->sendSms($phone, $message);

                if ($result['status']) {
                    $this->info('✓ SMS sent successfully!');
                    $this->line('  Message ID: ' . ($result['data']['message_id'] ?? 'N/A'));
                } else {
                    $this->error('✗ SMS failed: ' . $result['message']);
                }
            } catch (\Exception $e) {
                $this->error('✗ SMS Error: ' . $e->getMessage());
            }
            $this->newLine();
        }

        // Test WhatsApp
        if ($testWhatsApp) {
            $this->info('Testing WhatsApp...');
            try {
                $whatsappService = app(WhatsAppService::class);
                $message = sprintf(
                    "✅ *Test Notification*\n\nThis is a test WhatsApp message from %s.\n\nYour WhatsApp notifications are working correctly!",
                    config('app.name')
                );

                $result = $whatsappService->sendWhatsApp($phone, $message);

                if ($result['status']) {
                    $this->info('✓ WhatsApp sent successfully!');
                    $this->line('  Message ID: ' . ($result['data']['message_id'] ?? 'N/A'));
                } else {
                    $this->error('✗ WhatsApp failed: ' . $result['message']);
                }
            } catch (\Exception $e) {
                $this->error('✗ WhatsApp Error: ' . $e->getMessage());
            }
            $this->newLine();
        }

        $this->info('Test completed!');
        return 0;
    }
}
