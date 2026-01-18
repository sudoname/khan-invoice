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
        // Add invoice_id column
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('invoice_id')->nullable()->after('invoice_payment_id');
            $table->index('invoice_id');
        });

        // Update entry_type enum to include GATEWAY_FEE
        DB::statement("ALTER TABLE ledger_entries MODIFY COLUMN entry_type ENUM(
            'PAYMENT_RECEIVED',
            'GATEWAY_FEE',
            'PLATFORM_FEE',
            'PAYOUT',
            'REFUND',
            'CHARGEBACK',
            'ADJUSTMENT',
            'INSTANT_PAYOUT_FEE'
        ) NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->dropIndex(['invoice_id']);
            $table->dropColumn('invoice_id');
        });

        // Revert entry_type enum (remove GATEWAY_FEE)
        DB::statement("ALTER TABLE ledger_entries MODIFY COLUMN entry_type ENUM(
            'PAYMENT_RECEIVED',
            'PLATFORM_FEE',
            'PAYOUT',
            'REFUND',
            'CHARGEBACK',
            'ADJUSTMENT',
            'INSTANT_PAYOUT_FEE'
        ) NOT NULL");
    }
};
