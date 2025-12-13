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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('api_enabled')->default(false)->after('email_verified_at');
            $table->integer('api_rate_limit')->default(60)->after('api_enabled'); // requests per minute
            $table->timestamp('api_last_used_at')->nullable()->after('api_rate_limit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['api_enabled', 'api_rate_limit', 'api_last_used_at']);
        });
    }
};
