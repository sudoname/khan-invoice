<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class ResetSubscriptionUsage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:reset-usage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset monthly usage counters for all active subscriptions';

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
        $this->info('Resetting subscription usage counters...');

        $this->subscriptionService->resetMonthlyUsage();

        $this->info('✅ Usage counters reset completed');

        return Command::SUCCESS;
    }
}
