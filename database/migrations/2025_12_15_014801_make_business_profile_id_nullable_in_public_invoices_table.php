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
            // Check if column exists before modifying
            if (Schema::hasColumn('public_invoices', 'business_profile_id')) {
                $table->unsignedBigInteger('business_profile_id')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('public_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('public_invoices', 'business_profile_id')) {
                $table->unsignedBigInteger('business_profile_id')->nullable(false)->change();
            }
        });
    }
};
