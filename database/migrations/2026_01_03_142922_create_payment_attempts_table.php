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
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');

            // Provider info
            $table->string('provider', 50); // 'paystack', 'flutterwave', etc.
            $table->string('channel', 50)->nullable(); // 'card', 'bank_transfer', 'ussd'
            $table->string('reference')->unique(); // Provider reference
            $table->text('authorization_url')->nullable();

            // Status tracking
            $table->enum('status', [
                'INITIATED', 'PENDING', 'SUCCESS', 'FAILED', 'CANCELLED', 'ABANDONED'
            ])->default('INITIATED');

            // Amount details
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('NGN');
            $table->decimal('fees', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->nullable(); // amount - fees

            // Customer details
            $table->string('customer_email')->index();
            $table->string('customer_phone')->nullable();
            $table->string('customer_name')->nullable();

            // Provider response
            $table->json('metadata')->nullable(); // Full provider data
            $table->text('failure_reason')->nullable();

            // Timestamps
            $table->timestamp('initiated_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['invoice_id', 'status']);
            $table->index(['provider', 'reference']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
    }
};
