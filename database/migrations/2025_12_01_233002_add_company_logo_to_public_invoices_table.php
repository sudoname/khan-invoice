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
            $table->string('company_logo')->nullable()->after('from_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('public_invoices', function (Blueprint $table) {
            $table->dropColumn('company_logo');
        });
    }
};
