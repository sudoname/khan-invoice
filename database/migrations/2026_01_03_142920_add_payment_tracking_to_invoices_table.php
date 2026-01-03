<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Add amount_due column (for partial payments support)
            $table->decimal('amount_due', 15, 2)->nullable()->after('total_amount');

            // Add payment control columns
            $table->timestamp('last_payment_at')->nullable()->after('paid_at');
            $table->timestamp('payment_expires_at')->nullable()->after('last_payment_at');
            $table->boolean('payment_enabled')->default(true)->after('payment_expires_at');

            // Add indexes for new columns
            $table->index(['payment_enabled', 'payment_expires_at']);
            $table->index(['user_id', 'payment_status']);
        });

        // Backfill amount_due from total_amount for existing invoices
        DB::statement('UPDATE invoices SET amount_due = total_amount WHERE amount_due IS NULL');

        // Make amount_due non-nullable after backfill
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('amount_due', 15, 2)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['payment_enabled', 'payment_expires_at']);
            $table->dropIndex(['user_id', 'payment_status']);

            $table->dropColumn([
                'amount_due',
                'last_payment_at',
                'payment_expires_at',
                'payment_enabled',
            ]);
        });
    }
};
