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
        Schema::table('merchant_accounts', function (Blueprint $table) {
            $table->enum('account_type', ['savings', 'current', 'corporate', 'domiciliary'])
                ->default('savings')
                ->after('account_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchant_accounts', function (Blueprint $table) {
            $table->dropColumn('account_type');
        });
    }
};
