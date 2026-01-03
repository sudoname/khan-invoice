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
        Schema::create('payment_events', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Provider identification
            $table->string('provider', 50); // 'paystack', 'flutterwave'
            $table->string('event_type'); // 'charge.success', 'transfer.success'
            $table->string('reference')->index(); // Payment reference
            $table->string('event_id')->nullable(); // Provider's event ID

            // Idempotency
            $table->string('payload_hash', 64)->unique(); // SHA256 of payload
            $table->json('payload_json'); // Full webhook payload

            // Processing status
            $table->enum('status', ['RECEIVED', 'PROCESSING', 'PROCESSED', 'FAILED', 'DUPLICATE'])
                ->default('RECEIVED');
            $table->text('error_message')->nullable();

            // Timestamps
            $table->timestamp('received_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->unique(['provider', 'event_id'], 'unique_provider_event');
            $table->index(['provider', 'reference', 'event_type']);
            $table->index(['status', 'received_at']);
            $table->index('payload_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_events');
    }
};
