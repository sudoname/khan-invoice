<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RestoreDatabaseFromSQLite extends Command
{
    protected $signature = 'db:restore-from-sqlite {sqlite_path}';
    protected $description = 'Restore data from SQLite database to current database connection';

    public function handle()
    {
        $sqlitePath = $this->argument('sqlite_path');

        if (!file_exists($sqlitePath)) {
            $this->error("SQLite database file not found: {$sqlitePath}");
            return 1;
        }

        $this->info("Starting database restoration from: {$sqlitePath}");
        $this->info("Target database: " . config('database.default'));

        // Create SQLite connection
        config(['database.connections.restore_sqlite' => [
            'driver' => 'sqlite',
            'database' => $sqlitePath,
            'prefix' => '',
        ]]);

        $tables = ['users', 'customers', 'invoices', 'public_invoices'];

        foreach ($tables as $table) {
            $this->info("\nProcessing table: {$table}");

            // Get all records from SQLite
            $records = DB::connection('restore_sqlite')->table($table)->get();
            $count = $records->count();

            if ($count === 0) {
                $this->warn("No records found in {$table}");
                continue;
            }

            $this->info("Found {$count} records in {$table}");

            // Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // Clear existing data in target table
            DB::table($table)->truncate();

            // Insert records in batches
            $bar = $this->output->createProgressBar($count);
            $bar->start();

            foreach ($records->chunk(100) as $chunk) {
                foreach ($chunk as $record) {
                    // Convert to array
                    $data = json_decode(json_encode($record), true);

                    // Insert into target database
                    DB::table($table)->insert($data);
                    $bar->advance();
                }
            }

            $bar->finish();
            $this->newLine();

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            $this->info("Successfully restored {$count} records to {$table}");
        }

        // Verify restoration
        $this->info("\n=== Verification ===");
        foreach ($tables as $table) {
            $count = DB::table($table)->count();
            $this->info("{$table}: {$count} records");
        }

        $this->info("\n✓ Database restoration completed successfully!");

        return 0;
    }
}
