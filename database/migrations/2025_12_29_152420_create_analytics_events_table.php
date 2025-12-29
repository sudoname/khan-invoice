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
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->index(); // Event name
            $table->timestamp('occurred_at')->index(); // When event actually happened
            $table->string('path', 500)->nullable(); // URL path
            $table->string('referrer', 500)->nullable(); // Referrer URL

            // UTM parameters
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            $table->string('utm_term', 100)->nullable();
            $table->string('utm_content', 100)->nullable();

            // Session tracking
            $table->string('session_id', 100)->index()->nullable();
            $table->string('anonymous_id', 100)->index()->nullable();
            $table->foreignId('user_id')->nullable()->index()->constrained()->onDelete('set null');

            // Event properties (no PII)
            $table->json('properties')->nullable();

            // Privacy-safe tracking
            $table->string('ip_hash', 64)->nullable(); // SHA256 hash
            $table->string('user_agent', 500)->nullable();

            $table->timestamps();

            // Composite index for common queries
            $table->index(['name', 'occurred_at']);
            $table->index(['session_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
