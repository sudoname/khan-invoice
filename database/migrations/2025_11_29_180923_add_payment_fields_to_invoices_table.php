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
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('payment_reference')->nullable()->after('public_id');
            $table->enum('payment_status', ['pending', 'processing', 'completed', 'failed'])->default('pending')->after('payment_reference');
            $table->string('payment_gateway')->nullable()->after('payment_status');
            $table->timestamp('paid_at')->nullable()->after('payment_gateway');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['payment_reference', 'payment_status', 'payment_gateway', 'paid_at']);
        });
    }
};
