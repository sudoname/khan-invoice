<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('public_invoices', function (Blueprint $table) {
            // Simple invoice mode (no tax fields)
            $table->boolean('simple_mode')->default(false)->after('payment_status');

            // Status tracking for better invoice management
            $table->timestamp('sent_at')->nullable()->after('simple_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('public_invoices', function (Blueprint $table) {
            $table->dropColumn(['simple_mode', 'sent_at']);
        });
    }
};
