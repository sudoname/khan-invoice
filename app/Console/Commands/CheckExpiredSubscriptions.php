<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class CheckExpiredSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:check-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and expire subscriptions that have passed their expiration date';

    public function __construct(
        private SubscriptionService $subscriptionService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for expired subscriptions...');

        $this->subscriptionService->checkAndExpireSubscriptions();

        $this->info('✅ Expired subscriptions check completed');

        return Command::SUCCESS;
    }
}
