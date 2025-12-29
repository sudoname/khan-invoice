<?php

namespace App\Console\Commands\Analytics;

use App\Models\AnalyticsEvent;
use Illuminate\Console\Command;

class ViewEventsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:view
                            {--limit=100 : Number of events to display}
                            {--event= : Filter by specific event name}
                            {--today : Show only today\'s events}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'View recent analytics events';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = $this->option('limit');
        $eventName = $this->option('event');
        $todayOnly = $this->option('today');

        $query = AnalyticsEvent::query()->orderBy('occurred_at', 'desc');

        // Apply filters
        if ($eventName) {
            $query->where('name', $eventName);
        }

        if ($todayOnly) {
            $query->whereDate('occurred_at', today());
        }

        $events = $query->limit($limit)->get();

        if ($events->isEmpty()) {
            $this->info('No events found.');
            return Command::SUCCESS;
        }

        // Show summary statistics
        $this->info('=== Analytics Events Summary ===');
        $this->info('Total events: ' . $events->count());
        $this->info('Date range: ' . $events->last()->occurred_at->format('Y-m-d H:i') . ' to ' . $events->first()->occurred_at->format('Y-m-d H:i'));
        $this->newLine();

        // Show event breakdown
        $eventCounts = $events->groupBy('name')->map->count()->sortDesc();
        $this->info('Event breakdown:');
        foreach ($eventCounts as $name => $count) {
            $this->line("  - {$name}: {$count}");
        }
        $this->newLine();

        // Display events table
        $this->info('=== Recent Events ===');
        $headers = ['ID', 'Event Name', 'Occurred At', 'Path', 'User ID', 'Properties'];

        $rows = $events->map(function ($event) {
            $properties = $event->properties
                ? json_encode($event->properties, JSON_UNESCAPED_SLASHES)
                : '-';

            // Truncate long properties
            if (strlen($properties) > 50) {
                $properties = substr($properties, 0, 47) . '...';
            }

            return [
                $event->id,
                $event->name,
                $event->occurred_at->format('Y-m-d H:i:s'),
                $event->path ?? '-',
                $event->user_id ?? 'anonymous',
                $properties,
            ];
        });

        $this->table($headers, $rows);

        return Command::SUCCESS;
    }
}
