<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * CRITICAL: This migration adds database-level safeguards to prevent
     * accounting integrity violations discovered on 2026-02-16.
     *
     * Issue: Payouts were being created through Filament without corresponding
     * ledger entries, leading to accounting discrepancies.
     *
     * Safeguards added:
     * 1. Table comments documenting ledger entry requirements
     * 2. Index on ledger_entries.payout_id for performance
     * 3. Documentation in database schema
     */
    public function up(): void
    {
        // Add index on ledger_entries.payout_id if it doesn't exist
        // This improves performance when checking for ledger entries
        if (!$this->indexExists('ledger_entries', 'ledger_entries_payout_id_index')) {
            Schema::table('ledger_entries', function (Blueprint $table) {
                $table->index('payout_id', 'ledger_entries_payout_id_index');
            });
        }

        // Add composite index for payout ledger entry lookups
        if (!$this->indexExists('ledger_entries', 'ledger_entries_payout_lookup')) {
            Schema::table('ledger_entries', function (Blueprint $table) {
                $table->index(['payout_id', 'entry_type', 'account_type'], 'ledger_entries_payout_lookup');
            });
        }

        // Add table comments using raw SQL (cross-database compatible)
        $this->addTableComments();

        // Log this migration for audit trail
        DB::table('migrations')->where('migration', '2026_02_16_211212_add_accounting_integrity_documentation')
            ->update(['batch' => DB::raw('batch')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ($this->indexExists('ledger_entries', 'ledger_entries_payout_id_index')) {
            Schema::table('ledger_entries', function (Blueprint $table) {
                $table->dropIndex('ledger_entries_payout_id_index');
            });
        }

        if ($this->indexExists('ledger_entries', 'ledger_entries_payout_lookup')) {
            Schema::table('ledger_entries', function (Blueprint $table) {
                $table->dropIndex('ledger_entries_payout_lookup');
            });
        }
    }

    /**
     * Check if an index exists on a table
     */
    protected function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $schemaManager = $connection->getDoctrineSchemaManager();

        try {
            $indexes = $schemaManager->listTableIndexes($table);
            return isset($indexes[$index]);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Add table comments for documentation
     */
    protected function addTableComments(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            // MySQL-specific comments
            DB::statement("
                ALTER TABLE payouts COMMENT =
                'CRITICAL: Every payout MUST have corresponding ledger entries.
                Use PayoutService->createPayout() instead of direct database inserts.
                See PayoutObserver for enforcement rules.'
            ");

            DB::statement("
                ALTER TABLE ledger_entries COMMENT =
                'Immutable accounting ledger. Each payout must create DEBIT entries.
                Modifications should only be done through PayoutService.'
            ");
        } elseif ($driver === 'pgsql') {
            // PostgreSQL-specific comments
            DB::statement("
                COMMENT ON TABLE payouts IS
                'CRITICAL: Every payout MUST have corresponding ledger entries.
                Use PayoutService->createPayout() instead of direct database inserts.
                See PayoutObserver for enforcement rules.'
            ");

            DB::statement("
                COMMENT ON TABLE ledger_entries IS
                'Immutable accounting ledger. Each payout must create DEBIT entries.
                Modifications should only be done through PayoutService.'
            ");
        }

        // Log the documentation update
        logger()->info('Database accounting integrity documentation added', [
            'migration' => '2026_02_16_211212_add_accounting_integrity_documentation',
            'driver' => $driver,
        ]);
    }
};
