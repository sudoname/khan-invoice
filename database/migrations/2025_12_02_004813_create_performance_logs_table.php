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
        Schema::create('performance_logs', function (Blueprint $table) {
            $table->id();
            $table->decimal('query_time', 8, 2); // Database query time in milliseconds
            $table->decimal('cache_write_read_time', 8, 2); // Cache write/read time in milliseconds
            $table->timestamp('recorded_at');
            $table->timestamps();

            // Index for faster queries
            $table->index('recorded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_logs');
    }
};
