<?php

namespace App\Console\Commands;

use App\Models\PerformanceLog;
use Illuminate\Console\Command;

class LogPerformanceMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'performance:log';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Log current performance metrics (query time and cache time)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Logging performance metrics...');

        PerformanceLog::logCurrentMetrics();

        $this->info('Performance metrics logged successfully!');

        return Command::SUCCESS;
    }
}
