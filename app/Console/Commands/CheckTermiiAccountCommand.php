<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CheckTermiiAccountCommand extends Command
{
    protected $signature = 'termii:check';
    protected $description = 'Check Termii account configuration and available sender IDs';

    public function handle(): int
    {
        $apiKey = config('services.termii.api_key');

        if (!$apiKey) {
            $this->error('TERMII_API_KEY not configured!');
            return 1;
        }

        $this->info('Checking Termii account...');
        $this->newLine();

        // Check balance
        $this->info('1. Checking account balance...');
        $balanceResponse = Http::get('https://api.ng.termii.com/api/get-balance', [
            'api_key' => $apiKey,
        ]);

        if ($balanceResponse->successful()) {
            $balance = $balanceResponse->json();
            $this->info('   Balance: ' . ($balance['balance'] ?? 'N/A'));
            $this->info('   Currency: ' . ($balance['currency'] ?? 'N/A'));
        } else {
            $this->error('   Failed to get balance');
            $this->line('   Response: ' . $balanceResponse->body());
        }
        $this->newLine();

        // Check sender IDs
        $this->info('2. Checking registered sender IDs...');
        $senderResponse = Http::get('https://api.ng.termii.com/api/sender-id', [
            'api_key' => $apiKey,
        ]);

        if ($senderResponse->successful()) {
            $senders = $senderResponse->json();

            if (isset($senders['data']) && count($senders['data']) > 0) {
                $this->table(
                    ['Sender ID', 'Status', 'Created'],
                    collect($senders['data'])->map(function ($sender) {
                        return [
                            $sender['sender_id'] ?? 'N/A',
                            $sender['status'] ?? 'N/A',
                            $sender['created_at'] ?? 'N/A',
                        ];
                    })->toArray()
                );

                $this->newLine();
                $this->info('Available sender IDs: ' . collect($senders['data'])->pluck('sender_id')->implode(', '));
            } else {
                $this->warn('   No sender IDs registered yet');
                $this->line('   You need to register a sender ID in the Termii dashboard');
            }
        } else {
            $this->error('   Failed to get sender IDs');
            $this->line('   Response: ' . $senderResponse->body());
        }
        $this->newLine();

        // Suggest next steps
        $this->info('Next steps:');
        $this->line('1. Log in to https://termii.com/dashboard');
        $this->line('2. Go to Configuration → Sender ID');
        $this->line('3. Request a new Sender ID (e.g., "KhanInvoice" or your business name)');
        $this->line('4. Wait for approval (usually 24-48 hours)');
        $this->line('5. Update TERMII_SENDER_ID in .env with approved sender ID');

        return 0;
    }
}
