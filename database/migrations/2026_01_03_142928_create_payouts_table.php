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
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('merchant_account_id')
                ->constrained('merchant_accounts')
                ->onDelete('restrict');

            // Payout details
            $table->string('reference')->unique(); // Internal reference
            $table->decimal('gross_amount', 15, 2); // Before fees
            $table->decimal('payout_fee', 15, 2)->default(0); // Platform/provider fee
            $table->decimal('net_amount', 15, 2); // Actually sent
            $table->string('currency', 3)->default('NGN');

            // Payout type
            $table->enum('payout_type', ['STANDARD', 'INSTANT', 'MANUAL'])->default('MANUAL');
            $table->enum('status', [
                'PENDING', 'PROCESSING', 'COMPLETED', 'FAILED', 'REVERSED'
            ])->default('PENDING');

            // Provider details
            $table->string('provider', 50)->nullable(); // 'paystack', 'flutterwave'
            $table->string('provider_reference')->nullable(); // Provider's transfer reference
            $table->string('provider_transfer_code')->nullable(); // Provider's transfer code
            $table->json('provider_response')->nullable();

            // Status tracking
            $table->text('failure_reason')->nullable();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            // Settlement period (for batch payouts)
            $table->date('settlement_date')->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();

            // Admin approval (for manual payouts)
            $table->boolean('requires_approval')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['status', 'settlement_date']);
            $table->index('reference');
            $table->index('provider_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
