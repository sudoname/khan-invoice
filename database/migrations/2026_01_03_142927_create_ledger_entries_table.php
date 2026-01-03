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
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Entry type and classification
            $table->enum('entry_type', [
                'PAYMENT_RECEIVED', 'PLATFORM_FEE', 'PAYOUT', 'REFUND',
                'CHARGEBACK', 'ADJUSTMENT', 'INSTANT_PAYOUT_FEE'
            ]);
            $table->enum('account_type', ['DEBIT', 'CREDIT']);

            // Amount details
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2); // Running balance
            $table->string('currency', 3)->default('NGN');

            // Related entities (polymorphic-style tracking)
            $table->foreignUuid('invoice_payment_id')
                ->nullable()
                ->constrained('invoice_payments')
                ->onDelete('set null');
            $table->unsignedBigInteger('payout_id')->nullable(); // No FK constraint - payouts table created later

            // Metadata
            $table->text('description');
            $table->string('reference')->unique(); // Unique ledger reference
            $table->json('metadata')->nullable();

            // Reconciliation
            $table->boolean('is_reconciled')->default(false);
            $table->timestamp('reconciled_at')->nullable();

            // Timestamps
            $table->timestamp('entry_date')->useCurrent();
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'entry_date']);
            $table->index(['entry_type', 'entry_date']);
            $table->index(['user_id', 'is_reconciled']);
            $table->index('reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
