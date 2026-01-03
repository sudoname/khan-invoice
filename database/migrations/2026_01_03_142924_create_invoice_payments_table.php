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
        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Relationships
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('payment_attempt_id')
                ->constrained('payment_attempts')
                ->onDelete('cascade');

            // Payment details
            $table->decimal('amount_paid', 15, 2); // Actual amount paid
            $table->decimal('fees_paid', 15, 2)->default(0); // Transaction fees
            $table->decimal('net_received', 15, 2); // Amount after fees
            $table->string('currency', 3)->default('NGN');

            // Payment metadata
            $table->string('payment_method')->nullable(); // 'card', 'bank_transfer', 'ussd'
            $table->string('payment_reference'); // Transaction reference
            $table->json('payment_metadata')->nullable(); // Additional data from provider

            // Reconciliation status
            $table->enum('reconciliation_status', [
                'PENDING', 'MATCHED', 'RECONCILED', 'DISPUTED'
            ])->default('PENDING');
            $table->timestamp('reconciled_at')->nullable();

            // Timestamps
            $table->timestamp('paid_at')->useCurrent();
            $table->timestamps();

            // Indexes
            $table->index(['invoice_id', 'paid_at']);
            $table->index('payment_reference');
            $table->index(['reconciliation_status', 'paid_at']);
            $table->unique(['payment_attempt_id']); // One payment per attempt
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');
    }
};
